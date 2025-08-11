<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Biller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class BillerController extends Controller
{
    /**
     * Get all billers with filtering and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Biller::query();

            // Filter by client identifier
            if ($request->has('client_identifier')) {
                $query->where('client_identifier', $request->client_identifier);
            }

            // Filter by category
            if ($request->filled('category')) {
                $query->where('category', $request->category);
            }

            // Filter by active status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Search by name or description
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('contact_person', 'like', "%{$search}%");
                });
            }

            // Sort
            $sortBy = $request->get('sort_by', 'name');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginate
            $perPage = $request->get('per_page', 15);
            $billers = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $billers->items(),
                'pagination' => [
                    'current_page' => $billers->currentPage(),
                    'last_page' => $billers->lastPage(),
                    'per_page' => $billers->perPage(),
                    'total' => $billers->total(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch billers',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a new biller.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'contact_person' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:20',
                'website' => 'nullable|url|max:255',
                'address' => 'nullable|string',
                'account_number' => 'nullable|string|max:255',
                'category' => 'nullable|string|max:255',
                'is_active' => 'boolean',
                'client_identifier' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $biller = Biller::create($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Biller created successfully',
                'data' => $biller,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create biller',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific biller.
     */
    public function show($id): JsonResponse
    {
        try {
            $biller = Biller::with('billPayments')->find($id);

            if (!$biller) {
                return response()->json([
                    'success' => false,
                    'message' => 'Biller not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $biller,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch biller',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a biller.
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $biller = Biller::find($id);

            if (!$biller) {
                return response()->json([
                    'success' => false,
                    'message' => 'Biller not found',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'contact_person' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:20',
                'website' => 'nullable|url|max:255',
                'address' => 'nullable|string',
                'account_number' => 'nullable|string|max:255',
                'category' => 'nullable|string|max:255',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $biller->update($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Biller updated successfully',
                'data' => $biller,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update biller',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a biller.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $biller = Biller::find($id);

            if (!$biller) {
                return response()->json([
                    'success' => false,
                    'message' => 'Biller not found',
                ], 404);
            }

            // Check if biller has associated bill payments
            if ($biller->billPayments()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete biller with associated bill payments',
                ], 400);
            }

            $biller->delete();

            return response()->json([
                'success' => true,
                'message' => 'Biller deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete biller',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get biller categories.
     */
    public function categories(Request $request): JsonResponse
    {
        try {
            $categories = Biller::where('client_identifier', $request->client_identifier)
                ->whereNotNull('category')
                ->distinct()
                ->pluck('category')
                ->filter()
                ->values();

            return response()->json([
                'success' => true,
                'data' => $categories,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch categories',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk update biller status.
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'biller_ids' => 'required|array',
                'biller_ids.*' => 'integer|exists:billers,id',
                'is_active' => 'required|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            Biller::whereIn('id', $request->biller_ids)
                ->update(['is_active' => $request->is_active]);

            return response()->json([
                'success' => true,
                'message' => 'Billers status updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update billers status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk delete billers.
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'biller_ids' => 'required|array',
                'biller_ids.*' => 'integer|exists:billers,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Check if any biller has associated bill payments
            $billersWithPayments = Biller::whereIn('id', $request->biller_ids)
                ->whereHas('billPayments')
                ->count();

            if ($billersWithPayments > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete billers with associated bill payments',
                ], 400);
            }

            Biller::whereIn('id', $request->biller_ids)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Billers deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete billers',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
