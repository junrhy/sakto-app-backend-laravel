<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TravelPackage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TravelPackageController extends Controller
{
    /**
     * Display a listing of the travel packages.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_identifier' => ['required', 'string'],
            'status' => ['nullable', 'string'],
            'package_type' => ['nullable', 'string'],
            'search' => ['nullable', 'string'],
            'is_featured' => ['nullable', 'boolean'],
        ]);

        $query = TravelPackage::query()
            ->where('client_identifier', $validated['client_identifier'])
            ->latest();

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['package_type'])) {
            $query->where('package_type', $validated['package_type']);
        }

        if (array_key_exists('is_featured', $validated)) {
            $query->where('is_featured', (bool) $validated['is_featured']);
        }

        if (!empty($validated['search'])) {
            $query->where(function ($subQuery) use ($validated) {
                $subQuery
                    ->where('title', 'like', '%' . $validated['search'] . '%')
                    ->orWhere('description', 'like', '%' . $validated['search'] . '%');
            });
        }

        $packages = $query->paginate(
            $request->integer('per_page', 15)
        );

        return response()->json($packages);
    }

    /**
     * Store a newly created travel package.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_identifier' => ['required', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'duration_days' => ['nullable', 'integer', 'min:1'],
            'duration_label' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'inclusions' => ['nullable', 'array'],
            'inclusions.*' => ['string', 'max:255'],
            'package_type' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:100'],
            'is_featured' => ['nullable', 'boolean'],
            'media' => ['nullable', 'array'],
        ]);

        $slug = $validated['slug'] ?? Str::slug($validated['title']);

        $existing = TravelPackage::where('client_identifier', $validated['client_identifier'])
            ->where('slug', $slug)
            ->exists();

        if ($existing) {
            return response()->json([
                'status' => 'error',
                'message' => 'A travel package with this slug already exists.',
            ], 422);
        }

        $package = TravelPackage::create(array_merge($validated, [
            'slug' => $slug,
            'status' => $validated['status'] ?? 'draft',
            'package_type' => $validated['package_type'] ?? 'standard',
            'is_featured' => (bool) ($validated['is_featured'] ?? false),
        ]));

        return response()->json([
            'status' => 'success',
            'data' => $package->fresh(),
        ], 201);
    }

    /**
     * Display the specified travel package.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'client_identifier' => ['required', 'string'],
        ]);

        $package = TravelPackage::where('client_identifier', $validated['client_identifier'])
            ->with('bookings')
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $package,
        ]);
    }

    /**
     * Update the specified travel package.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'client_identifier' => ['required', 'string'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'duration_days' => ['nullable', 'integer', 'min:1'],
            'duration_label' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'inclusions' => ['nullable', 'array'],
            'inclusions.*' => ['string', 'max:255'],
            'package_type' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:100'],
            'is_featured' => ['nullable', 'boolean'],
            'media' => ['nullable', 'array'],
        ]);

        $package = TravelPackage::where('client_identifier', $validated['client_identifier'])
            ->findOrFail($id);

        if (!empty($validated['slug']) && $validated['slug'] !== $package->slug) {
            $slugExists = TravelPackage::where('client_identifier', $validated['client_identifier'])
                ->where('slug', $validated['slug'])
                ->where('id', '!=', $package->id)
                ->exists();

            if ($slugExists) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'A travel package with this slug already exists.',
                ], 422);
            }
        }

        $package->fill($validated);

        if (isset($validated['is_featured'])) {
            $package->is_featured = (bool) $validated['is_featured'];
        }

        $package->save();

        return response()->json([
            'status' => 'success',
            'data' => $package->fresh(),
        ]);
    }

    /**
     * Remove the specified travel package.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'client_identifier' => ['required', 'string'],
        ]);

        $package = TravelPackage::where('client_identifier', $validated['client_identifier'])
            ->findOrFail($id);

        $package->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Travel package deleted successfully.',
        ]);
    }

    /**
     * Toggle featured status.
     */
    public function toggleFeatured(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'client_identifier' => ['required', 'string'],
            'is_featured' => ['required', 'boolean'],
        ]);

        $package = TravelPackage::where('client_identifier', $validated['client_identifier'])
            ->findOrFail($id);

        $package->is_featured = (bool) $validated['is_featured'];
        $package->save();

        return response()->json([
            'status' => 'success',
            'data' => $package->fresh(),
        ]);
    }

    /**
     * Update publish status.
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'client_identifier' => ['required', 'string'],
            'status' => ['required', 'string', 'max:100'],
        ]);

        $package = TravelPackage::where('client_identifier', $validated['client_identifier'])
            ->findOrFail($id);

        $package->status = $validated['status'];
        $package->save();

        return response()->json([
            'status' => 'success',
            'data' => $package->fresh(),
        ]);
    }
}

