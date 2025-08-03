<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Display a listing of products for a client
     */
    public function index(Request $request): JsonResponse
    {
        $clientIdentifier = $request->query('client_identifier');
        
        if (!$clientIdentifier) {
            return response()->json(['error' => 'Client identifier is required'], 400);
        }

        $query = Product::forClient($clientIdentifier);

        // Apply filters
        if ($request->has('type')) {
            $query->byType($request->type);
        }

        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhereJsonContains('tags', $search);
            });
        }

        // Apply stock filters
        if ($request->has('stock_status')) {
            switch ($request->stock_status) {
                case 'in_stock':
                    $query->inStock();
                    break;
                case 'low_stock':
                    $query->lowStock();
                    break;
                case 'out_of_stock':
                    $query->outOfStock();
                    break;
            }
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $products = $query->with(['activeVariants', 'images'])->get();

        return response()->json($products);
    }

    /**
     * Store a newly created product
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'category' => 'required|string|max:255',
            'type' => ['required', Rule::in(['physical', 'digital', 'service', 'subscription'])],
            'sku' => 'nullable|string|max:255',
            'stock_quantity' => 'nullable|integer|min:0',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|string|max:255',
            'file_url' => 'nullable|string',
            'thumbnail_url' => 'nullable|string',
            'status' => ['required', Rule::in(['draft', 'published', 'archived', 'inactive'])],
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:255',
            'metadata' => 'nullable|array',
            // Multiple suppliers and purchase records
            'suppliers' => 'nullable|array',
            'suppliers.*.name' => 'required_with:suppliers|string|max:255',
            'suppliers.*.email' => 'nullable|email|max:255',
            'suppliers.*.phone' => 'nullable|string|max:255',
            'suppliers.*.website' => 'nullable|url|max:255',
            'suppliers.*.contact_person' => 'nullable|string|max:255',
            'suppliers.*.address' => 'nullable|string|max:500',
            'purchase_records' => 'nullable|array',
            'purchase_records.*.supplier_id' => 'nullable',
            'purchase_records.*.price' => 'required_with:purchase_records|numeric|min:0',
            'purchase_records.*.currency' => 'nullable|string|max:10',
            'purchase_records.*.date' => 'nullable|date',
            'purchase_records.*.order_number' => 'nullable|string|max:255',
            'purchase_records.*.notes' => 'nullable|string|max:1000',
            'purchase_records.*.reorder_point' => 'nullable|integer|min:0',
            'purchase_records.*.reorder_quantity' => 'nullable|integer|min:0',
            'purchase_records.*.lead_time_days' => 'nullable|integer|min:0',
            'purchase_records.*.payment_terms' => 'nullable|string|max:255',
            'client_identifier' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        // Extract suppliers and purchase records before creating product
        $suppliers = $data['suppliers'] ?? [];
        $purchaseRecords = $data['purchase_records'] ?? [];
        unset($data['suppliers'], $data['purchase_records']);

        // Set default stock quantity for physical products
        if ($data['type'] === 'physical' && !isset($data['stock_quantity'])) {
            $data['stock_quantity'] = 0;
        }

        $product = Product::create($data);

        // Create suppliers and store mapping of frontend IDs to database IDs
        $supplierIdMapping = [];
        foreach ($suppliers as $index => $supplierData) {
            $supplier = $product->suppliers()->create($supplierData);
            // Map the frontend-generated ID to the actual database ID
            if (isset($supplierData['id'])) {
                $supplierIdMapping[$supplierData['id']] = $supplier->id;
            }
        }

        // Create purchase records
        foreach ($purchaseRecords as $purchaseData) {
            // Map supplier_id to product_supplier_id if it exists
            if (isset($purchaseData['supplier_id']) && !empty($purchaseData['supplier_id'])) {
                if (isset($supplierIdMapping[$purchaseData['supplier_id']])) {
                    $purchaseData['product_supplier_id'] = $supplierIdMapping[$purchaseData['supplier_id']];
                }
            }
            unset($purchaseData['supplier_id']);
            
            $product->purchaseRecords()->create($purchaseData);
        }

        // Load relationships for response
        $product->load(['suppliers', 'purchaseRecords']);

        return response()->json($product, 201);
    }

    /**
     * Display the specified product
     */
    public function show(string $id): JsonResponse
    {
        $product = Product::with(['activeVariants', 'images', 'suppliers', 'purchaseRecords'])->find($id);

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        return response()->json($product->toArray());
    }

    /**
     * Update the specified product
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {   
            $product = Product::find($id);

            if (!$product) {
                return response()->json(['error' => 'Product not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|required|string',
                'price' => 'sometimes|required|numeric|min:0',
                'category' => 'sometimes|required|string|max:255',
                'type' => ['sometimes', 'required', Rule::in(['physical', 'digital', 'service', 'subscription'])],
                'sku' => 'nullable|string|max:255',
                'stock_quantity' => 'nullable|integer|min:0',
                'weight' => 'nullable|numeric|min:0',
                'dimensions' => 'nullable|string|max:255',
                'file_url' => 'nullable|string',
                'thumbnail_url' => 'nullable|string',
                'status' => ['sometimes', 'required', Rule::in(['draft', 'published', 'archived', 'inactive'])],
                'tags' => 'nullable|array',
                'tags.*' => 'string|max:255',
                'metadata' => 'nullable|array',
                // Multiple suppliers and purchase records
                'suppliers' => 'nullable|array',
                'suppliers.*.id' => 'required',
                'suppliers.*.name' => 'required_with:suppliers|string|max:255',
                'suppliers.*.email' => 'nullable|email|max:255',
                'suppliers.*.phone' => 'nullable|string|max:255',
                'suppliers.*.website' => 'nullable|url|max:255',
                'suppliers.*.contact_person' => 'nullable|string|max:255',
                'suppliers.*.address' => 'nullable|string|max:500',
                'purchase_records' => 'nullable|array',
                'purchase_records.*.id' => 'nullable',
                'purchase_records.*.supplier_id' => 'nullable',
                'purchase_records.*.price' => 'required_with:purchase_records|numeric|min:0',
                'purchase_records.*.currency' => 'nullable|string|max:10',
                'purchase_records.*.date' => 'nullable|date',
                'purchase_records.*.order_number' => 'nullable|string|max:255',
                'purchase_records.*.notes' => 'nullable|string|max:1000',
                'purchase_records.*.reorder_point' => 'nullable|integer|min:0',
                'purchase_records.*.reorder_quantity' => 'nullable|integer|min:0',
                'purchase_records.*.lead_time_days' => 'nullable|integer|min:0',
                'purchase_records.*.payment_terms' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $data = $validator->validated();

            // Extract suppliers and purchase records before updating product
            $suppliers = $data['suppliers'] ?? [];
            $purchaseRecords = $data['purchase_records'] ?? [];
            unset($data['suppliers'], $data['purchase_records']);

            // Set default stock quantity for physical products if type is being changed
            if (isset($data['type']) && $data['type'] === 'physical' && !isset($data['stock_quantity'])) {
                $data['stock_quantity'] = 0;
            }

            $product->update($data);

            // Handle suppliers
            if (isset($suppliers)) {
                // Get existing supplier IDs
                $existingSupplierIds = $product->suppliers()->pluck('id')->toArray();
                $updatedSupplierIds = [];
                $supplierIdMapping = [];

                foreach ($suppliers as $supplierData) {
                    if (isset($supplierData['id']) && is_numeric($supplierData['id'])) {
                        // Update existing supplier (numeric ID means it exists in database)
                        $supplier = $product->suppliers()->find($supplierData['id']);
                        if ($supplier) {
                            $supplier->update($supplierData);
                            $updatedSupplierIds[] = $supplier->id;
                            $supplierIdMapping[$supplierData['id']] = $supplier->id;
                        }
                    } else {
                        // Create new supplier (string ID or no ID means it's new)
                        $frontendId = $supplierData['id'] ?? null;
                        unset($supplierData['id']); // Remove the frontend-generated ID
                        $supplier = $product->suppliers()->create($supplierData);
                        $updatedSupplierIds[] = $supplier->id;
                        if ($frontendId) {
                            $supplierIdMapping[$frontendId] = $supplier->id;
                        }
                    }
                }

                // Delete suppliers that are no longer in the list
                $suppliersToDelete = array_diff($existingSupplierIds, $updatedSupplierIds);
                if (!empty($suppliersToDelete)) {
                    $product->suppliers()->whereIn('id', $suppliersToDelete)->delete();
                }
            }

            // Handle purchase records
            if (isset($purchaseRecords)) {
                // Get existing purchase record IDs
                $existingPurchaseIds = $product->purchaseRecords()->pluck('id')->toArray();
                $updatedPurchaseIds = [];

                foreach ($purchaseRecords as $purchaseData) {
                    // Map supplier_id to product_supplier_id if it exists
                    if (isset($purchaseData['supplier_id']) && !empty($purchaseData['supplier_id'])) {
                        if (isset($supplierIdMapping[$purchaseData['supplier_id']])) {
                            $purchaseData['product_supplier_id'] = $supplierIdMapping[$purchaseData['supplier_id']];
                        } else {
                            // Fallback: try to find by database ID
                            $supplier = $product->suppliers()->where('id', $purchaseData['supplier_id'])->first();
                            if ($supplier) {
                                $purchaseData['product_supplier_id'] = $supplier->id;
                            }
                        }
                    }
                    unset($purchaseData['supplier_id']);

                    if (isset($purchaseData['id']) && is_numeric($purchaseData['id'])) {
                        // Update existing purchase record (numeric ID means it exists in database)
                        $purchaseRecord = $product->purchaseRecords()->find($purchaseData['id']);
                        if ($purchaseRecord) {
                            $purchaseRecord->update($purchaseData);
                            $updatedPurchaseIds[] = $purchaseRecord->id;
                        }
                    } else {
                        // Create new purchase record (string ID or no ID means it's new)
                        unset($purchaseData['id']); // Remove the frontend-generated ID
                        $purchaseRecord = $product->purchaseRecords()->create($purchaseData);
                        $updatedPurchaseIds[] = $purchaseRecord->id;
                    }
                }

                // Delete purchase records that are no longer in the list
                $purchasesToDelete = array_diff($existingPurchaseIds, $updatedPurchaseIds);
                if (!empty($purchasesToDelete)) {
                    $product->purchaseRecords()->whereIn('id', $purchasesToDelete)->delete();
                }
            }

            // Reload the product with all relationships
            $product->load(['activeVariants', 'suppliers', 'purchaseRecords']);

            return response()->json($product);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified product
     */
    public function destroy(string $id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        // Delete associated files if they exist
        if ($product->file_url) {
            $this->deleteFile($product->file_url);
        }

        if ($product->thumbnail_url) {
            $this->deleteFile($product->thumbnail_url);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }

    /**
     * Update product stock quantity
     */
    public function updateStock(Request $request, string $id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        if ($product->type !== 'physical') {
            return response()->json(['error' => 'Stock can only be updated for physical products'], 400);
        }

        $validator = Validator::make($request->all(), [
            'stock_quantity' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $product->update($validator->validated());

        return response()->json($product);
    }

    /**
     * Download product file (for digital products)
     */
    public function download(string $id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        if ($product->type !== 'digital') {
            return response()->json(['error' => 'Download is only available for digital products'], 400);
        }

        if (!$product->file_url) {
            return response()->json(['error' => 'No file available for download'], 404);
        }

        // Return file information for download
        return response()->json([
            'product' => $product,
            'download_url' => $product->file_url,
            'file_name' => $product->file_name,
            'file_extension' => $product->file_extension,
        ]);
    }

    /**
     * Get product categories for a client
     */
    public function getCategories(Request $request): JsonResponse
    {
        $clientIdentifier = $request->query('client_identifier');
        
        if (!$clientIdentifier) {
            return response()->json(['error' => 'Client identifier is required'], 400);
        }

        $categories = Product::forClient($clientIdentifier)
            ->distinct()
            ->pluck('category')
            ->filter()
            ->values();

        return response()->json($categories);
    }

    /**
     * Get product settings/statistics for a client
     */
    public function getSettings(Request $request): JsonResponse
    {
        $clientIdentifier = $request->query('client_identifier');
        
        if (!$clientIdentifier) {
            return response()->json(['error' => 'Client identifier is required'], 400);
        }

        $products = Product::forClient($clientIdentifier);

        $stats = [
            'total_products' => $products->count(),
            'by_type' => [
                'physical' => $products->byType('physical')->count(),
                'digital' => $products->byType('digital')->count(),
                'service' => $products->byType('service')->count(),
                'subscription' => $products->byType('subscription')->count(),
            ],
            'by_status' => [
                'draft' => $products->byStatus('draft')->count(),
                'published' => $products->byStatus('published')->count(),
                'archived' => $products->byStatus('archived')->count(),
                'inactive' => $products->byStatus('inactive')->count(),
            ],
            'stock_status' => [
                'in_stock' => $products->inStock()->count(),
                'low_stock' => $products->lowStock()->count(),
                'out_of_stock' => $products->outOfStock()->count(),
            ],
            'categories' => $products->distinct()->pluck('category')->filter()->values(),
        ];

        return response()->json($stats);
    }

    /**
     * Bulk delete products
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_ids' => 'required|array',
            'product_ids.*' => 'integer|exists:products,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $productIds = $request->product_ids;
        $products = Product::whereIn('id', $productIds)->get();

        foreach ($products as $product) {
            // Delete associated files
            if ($product->file_url) {
                $this->deleteFile($product->file_url);
            }

            if ($product->thumbnail_url) {
                $this->deleteFile($product->thumbnail_url);
            }
        }

        Product::whereIn('id', $productIds)->delete();

        return response()->json(['message' => 'Products deleted successfully']);
    }

    /**
     * Delete file from storage
     */
    private function deleteFile(string $fileUrl): void
    {
        try {
            // Extract the path from the URL
            $path = str_replace(config('app.url') . '/storage/', '', $fileUrl);
            
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        } catch (\Exception $e) {
            // Log error but don't throw exception
            \Log::error('Failed to delete file: ' . $fileUrl, ['error' => $e->getMessage()]);
        }
    }
}
