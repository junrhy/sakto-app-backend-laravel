<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobBoard;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class JobBoardController extends Controller
{
    /**
     * Display a listing of job boards for a client
     */
    public function index(Request $request): JsonResponse
    {
        $clientIdentifier = $request->query('client_identifier');
        
        if (!$clientIdentifier) {
            return response()->json(['error' => 'Client identifier is required'], 400);
        }

        $query = JobBoard::forClient($clientIdentifier);

        // Apply filters
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $jobBoards = $query->withCount(['jobs', 'publishedJobs'])->get();

        return response()->json([
            'status' => 'success',
            'data' => $jobBoards
        ]);
    }

    /**
     * Store a newly created job board
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'slug' => 'nullable|string|max:255|unique:job_boards,slug',
            'is_active' => 'nullable|boolean',
            'settings' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        
        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
            // Ensure uniqueness
            $originalSlug = $data['slug'];
            $counter = 1;
            while (JobBoard::where('slug', $data['slug'])->exists()) {
                $data['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }
        }

        $jobBoard = JobBoard::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Job board created successfully',
            'data' => $jobBoard
        ], 201);
    }

    /**
     * Display the specified job board
     */
    public function show(string $id): JsonResponse
    {
        $jobBoard = JobBoard::with(['jobs' => function ($query) {
            $query->orderBy('created_at', 'desc');
        }])->withCount(['jobs', 'publishedJobs'])->find($id);

        if (!$jobBoard) {
            return response()->json([
                'status' => 'error',
                'message' => 'Job board not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $jobBoard
        ]);
    }

    /**
     * Update the specified job board
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $jobBoard = JobBoard::find($id);

        if (!$jobBoard) {
            return response()->json([
                'status' => 'error',
                'message' => 'Job board not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'slug' => 'nullable|string|max:255|unique:job_boards,slug,' . $id,
            'is_active' => 'nullable|boolean',
            'settings' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // Generate slug if name changed and slug not provided
        if (isset($data['name']) && empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
            // Ensure uniqueness
            $originalSlug = $data['slug'];
            $counter = 1;
            while (JobBoard::where('slug', $data['slug'])->where('id', '!=', $id)->exists()) {
                $data['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }
        }

        $jobBoard->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Job board updated successfully',
            'data' => $jobBoard->fresh()
        ]);
    }

    /**
     * Remove the specified job board
     */
    public function destroy(string $id): JsonResponse
    {
        $jobBoard = JobBoard::find($id);

        if (!$jobBoard) {
            return response()->json([
                'status' => 'error',
                'message' => 'Job board not found'
            ], 404);
        }

        $jobBoard->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Job board deleted successfully'
        ]);
    }
}
