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

        $products = $query->with('activeVariants')->get();

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
            'client_identifier' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        // Set default stock quantity for physical products
        if ($data['type'] === 'physical' && !isset($data['stock_quantity'])) {
            $data['stock_quantity'] = 0;
        }

        $product = Product::create($data);

        return response()->json($product, 201);
    }

    /**
     * Display the specified product
     */
    public function show(string $id): JsonResponse
    {
        $product = Product::with('activeVariants')->find($id);

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
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        // Set default stock quantity for physical products if type is being changed
        if (isset($data['type']) && $data['type'] === 'physical' && !isset($data['stock_quantity'])) {
            $data['stock_quantity'] = 0;
        }

        $product->update($data);

        // Reload the product with variants
        $product->load('activeVariants');

        return response()->json($product);
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
