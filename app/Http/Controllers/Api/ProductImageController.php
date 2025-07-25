<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductImageController extends Controller
{
    /**
     * Get all images for a product
     */
    public function index(string $productId): JsonResponse
    {
        $product = Product::with('images')->find($productId);

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        return response()->json($product->images);
    }

    /**
     * Store a new image for a product
     */
    public function store(Request $request, string $productId): JsonResponse
    {
        $product = Product::find($productId);

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'image_url' => 'required|string|url',
            'alt_text' => 'nullable|string|max:255',
            'is_primary' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        // If this is set as primary, unset other primary images
        if ($data['is_primary'] ?? false) {
            $product->images()->update(['is_primary' => false]);
        }

        // Create the image record
        $image = $product->images()->create([
            'image_url' => $data['image_url'],
            'alt_text' => $data['alt_text'] ?? null,
            'is_primary' => $data['is_primary'] ?? false,
            'sort_order' => $data['sort_order'] ?? $product->images()->count(),
        ]);

        return response()->json($image, 201);
    }

    /**
     * Update an image
     */
    public function update(Request $request, string $productId, string $imageId): JsonResponse
    {
        $product = Product::find($productId);
        $image = ProductImage::where('product_id', $productId)->find($imageId);

        if (!$product || !$image) {
            return response()->json(['error' => 'Product or image not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'alt_text' => 'nullable|string|max:255',
            'is_primary' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        // If this is set as primary, unset other primary images
        if ($data['is_primary'] ?? false) {
            $product->images()->where('id', '!=', $imageId)->update(['is_primary' => false]);
        }

        $image->update($data);

        return response()->json($image);
    }

    /**
     * Delete an image
     */
    public function destroy(string $productId, string $imageId): JsonResponse
    {
        $image = ProductImage::where('product_id', $productId)->find($imageId);

        if (!$image) {
            return response()->json(['error' => 'Image not found'], 404);
        }

        // Note: File deletion is handled by the frontend controller
        // We only delete the database record here
        $image->delete();

        return response()->json(['message' => 'Image deleted successfully']);
    }

    /**
     * Reorder images
     */
    public function reorder(Request $request, string $productId): JsonResponse
    {
        $product = Product::find($productId);

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'image_order' => 'required|array',
            'image_order.*' => 'integer|exists:product_images,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $imageOrder = $request->input('image_order');

        foreach ($imageOrder as $index => $imageId) {
            ProductImage::where('id', $imageId)
                ->where('product_id', $productId)
                ->update(['sort_order' => $index]);
        }

        return response()->json(['message' => 'Images reordered successfully']);
    }
}
