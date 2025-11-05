<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class JobController extends Controller
{
    /**
     * Display a listing of jobs
     */
    public function index(Request $request): JsonResponse
    {
        $clientIdentifier = $request->query('client_identifier');
        
        if (!$clientIdentifier) {
            return response()->json(['error' => 'Client identifier is required'], 400);
        }

        $query = Job::forClient($clientIdentifier);

        // Apply filters
        if ($request->has('job_board_id')) {
            $query->byJobBoard($request->job_board_id);
        }

        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        if ($request->has('employment_type')) {
            $query->where('employment_type', $request->employment_type);
        }

        if ($request->has('job_category')) {
            $query->where('job_category', $request->job_category);
        }

        if ($request->has('is_featured')) {
            $query->where('is_featured', $request->boolean('is_featured'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $jobs = $query->with(['jobBoard', 'applications'])->withCount('applications')->get();

        return response()->json([
            'status' => 'success',
            'data' => $jobs
        ]);
    }

    /**
     * Store a newly created job
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string',
            'job_board_id' => 'required|exists:job_boards,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'requirements' => 'nullable|string',
            'salary_min' => 'nullable|numeric|min:0',
            'salary_max' => 'nullable|numeric|min:0|gte:salary_min',
            'salary_currency' => 'nullable|string|max:10',
            'location' => 'nullable|string|max:255',
            'employment_type' => 'nullable|string|max:255',
            'job_category' => 'nullable|string|max:255',
            'status' => ['nullable', Rule::in(['draft', 'published', 'closed'])],
            'application_deadline' => 'nullable|date',
            'application_url' => 'nullable|url',
            'application_email' => 'nullable|email',
            'is_featured' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['status'] = $data['status'] ?? 'draft';
        $data['salary_currency'] = $data['salary_currency'] ?? 'PHP';

        $job = Job::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Job created successfully',
            'data' => $job->load('jobBoard')
        ], 201);
    }

    /**
     * Display the specified job
     */
    public function show(string $id): JsonResponse
    {
        $job = Job::with(['jobBoard', 'applications.applicant'])
            ->withCount('applications')
            ->find($id);

        if (!$job) {
            return response()->json([
                'status' => 'error',
                'message' => 'Job not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $job
        ]);
    }

    /**
     * Update the specified job
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $job = Job::find($id);

        if (!$job) {
            return response()->json([
                'status' => 'error',
                'message' => 'Job not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'job_board_id' => 'sometimes|required|exists:job_boards,id',
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'requirements' => 'nullable|string',
            'salary_min' => 'nullable|numeric|min:0',
            'salary_max' => 'nullable|numeric|min:0|gte:salary_min',
            'salary_currency' => 'nullable|string|max:10',
            'location' => 'nullable|string|max:255',
            'employment_type' => 'nullable|string|max:255',
            'job_category' => 'nullable|string|max:255',
            'status' => ['nullable', Rule::in(['draft', 'published', 'closed'])],
            'application_deadline' => 'nullable|date',
            'application_url' => 'nullable|url',
            'application_email' => 'nullable|email',
            'is_featured' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $job->update($validator->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Job updated successfully',
            'data' => $job->fresh()->load('jobBoard')
        ]);
    }

    /**
     * Remove the specified job
     */
    public function destroy(string $id): JsonResponse
    {
        $job = Job::find($id);

        if (!$job) {
            return response()->json([
                'status' => 'error',
                'message' => 'Job not found'
            ], 404);
        }

        $job->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Job deleted successfully'
        ]);
    }

    /**
     * Publish a job
     */
    public function publish(string $id): JsonResponse
    {
        $job = Job::find($id);

        if (!$job) {
            return response()->json([
                'status' => 'error',
                'message' => 'Job not found'
            ], 404);
        }

        $job->update(['status' => 'published']);

        return response()->json([
            'status' => 'success',
            'message' => 'Job published successfully',
            'data' => $job->fresh()
        ]);
    }

    /**
     * Close a job
     */
    public function close(string $id): JsonResponse
    {
        $job = Job::find($id);

        if (!$job) {
            return response()->json([
                'status' => 'error',
                'message' => 'Job not found'
            ], 404);
        }

        $job->update(['status' => 'closed']);

        return response()->json([
            'status' => 'success',
            'message' => 'Job closed successfully',
            'data' => $job->fresh()
        ]);
    }
}
