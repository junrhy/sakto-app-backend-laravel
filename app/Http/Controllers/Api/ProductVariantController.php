<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ProductVariantController extends Controller
{
    /**
     * Display a listing of variants for a product
     */
    public function index(Request $request, string $productId): JsonResponse
    {
        $product = Product::find($productId);

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        $variants = $product->variants()->active()->get();

        return response()->json($variants);
    }

    /**
     * Store a newly created variant
     */
    public function store(Request $request, string $productId): JsonResponse
    {
        $product = Product::find($productId);

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        if ($product->type !== 'physical') {
            return response()->json(['error' => 'Variants can only be added to physical products'], 400);
        }

        $validator = Validator::make($request->all(), [
            'sku' => 'nullable|string|max:255|unique:product_variants,sku',
            'price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|string|max:255',
            'thumbnail_url' => 'nullable|string',
            'attributes' => 'required|array|min:1',
            'attributes.*' => 'string|max:255',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['product_id'] = $productId;

        // Check if variant with same attributes already exists
        $existingVariant = $product->variants()
            ->where('attributes', json_encode($data['attributes']))
            ->first();

        if ($existingVariant) {
            return response()->json(['error' => 'A variant with these attributes already exists'], 422);
        }

        $variant = ProductVariant::create($data);

        return response()->json($variant, 201);
    }

    /**
     * Display the specified variant
     */
    public function show(string $productId, string $variantId): JsonResponse
    {
        $variant = ProductVariant::where('product_id', $productId)
            ->where('id', $variantId)
            ->first();

        if (!$variant) {
            return response()->json(['error' => 'Variant not found'], 404);
        }

        return response()->json($variant);
    }

    /**
     * Update the specified variant
     */
    public function update(Request $request, string $productId, string $variantId): JsonResponse
    {
        $variant = ProductVariant::where('product_id', $productId)
            ->where('id', $variantId)
            ->first();

        if (!$variant) {
            return response()->json(['error' => 'Variant not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'sku' => 'nullable|string|max:255|unique:product_variants,sku,' . $variantId,
            'price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'sometimes|required|integer|min:0',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|string|max:255',
            'thumbnail_url' => 'nullable|string',
            'attributes' => 'sometimes|required|array|min:1',
            'attributes.*' => 'string|max:255',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        // Check if variant with same attributes already exists (excluding current variant)
        if (isset($data['attributes'])) {
            $existingVariant = $variant->product->variants()
                ->where('id', '!=', $variantId)
                ->where('attributes', json_encode($data['attributes']))
                ->first();

            if ($existingVariant) {
                return response()->json(['error' => 'A variant with these attributes already exists'], 422);
            }
        }

        $variant->update($data);

        return response()->json($variant);
    }

    /**
     * Remove the specified variant
     */
    public function destroy(string $productId, string $variantId): JsonResponse
    {
        $variant = ProductVariant::where('product_id', $productId)
            ->where('id', $variantId)
            ->first();

        if (!$variant) {
            return response()->json(['error' => 'Variant not found'], 404);
        }

        // Delete variant thumbnail if it exists
        if ($variant->thumbnail_url) {
            $this->deleteFile($variant->thumbnail_url);
        }

        $variant->delete();

        return response()->json(['message' => 'Variant deleted successfully']);
    }

    /**
     * Update stock for a specific variant
     */
    public function updateStock(Request $request, string $productId, string $variantId): JsonResponse
    {
        $variant = ProductVariant::where('product_id', $productId)
            ->where('id', $variantId)
            ->first();

        if (!$variant) {
            return response()->json(['error' => 'Variant not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'stock_quantity' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $variant->update($validator->validated());

        return response()->json($variant);
    }

    /**
     * Get available attribute options for a product
     */
    public function getAttributes(string $productId): JsonResponse
    {
        $product = Product::find($productId);

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        return response()->json($product->available_attributes);
    }

    /**
     * Bulk update variants
     */
    public function bulkUpdate(Request $request, string $productId): JsonResponse
    {
        $product = Product::find($productId);

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'variants' => 'required|array|min:1',
            'variants.*.id' => 'required|exists:product_variants,id',
            'variants.*.stock_quantity' => 'required|integer|min:0',
            'variants.*.price' => 'nullable|numeric|min:0',
            'variants.*.is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $updatedVariants = [];
        foreach ($request->variants as $variantData) {
            $variant = ProductVariant::find($variantData['id']);
            if ($variant && $variant->product_id == $productId) {
                $variant->update([
                    'stock_quantity' => $variantData['stock_quantity'],
                    'price' => $variantData['price'] ?? $variant->price,
                    'is_active' => $variantData['is_active'] ?? $variant->is_active,
                ]);
                $updatedVariants[] = $variant;
            }
        }

        return response()->json([
            'message' => 'Variants updated successfully',
            'variants' => $updatedVariants
        ]);
    }

    /**
     * Delete a file from storage
     */
    private function deleteFile(string $fileUrl): void
    {
        try {
            $path = str_replace(Storage::disk('public')->url(''), '', $fileUrl);
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        } catch (\Exception $e) {
            // Log error but don't fail the operation
            \Log::error('Failed to delete file: ' . $fileUrl, ['error' => $e->getMessage()]);
        }
    }
} 