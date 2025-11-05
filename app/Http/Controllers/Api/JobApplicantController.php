<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobApplicant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class JobApplicantController extends Controller
{
    /**
     * Display a listing of applicants
     */
    public function index(Request $request): JsonResponse
    {
        $clientIdentifier = $request->query('client_identifier');
        
        if (!$clientIdentifier) {
            return response()->json(['error' => 'Client identifier is required'], 400);
        }

        $query = JobApplicant::forClient($clientIdentifier);

        // Apply filters
        if ($request->has('email')) {
            $query->byEmail($request->email);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $applicants = $query->withCount('applications')->get();

        return response()->json([
            'status' => 'success',
            'data' => $applicants
        ]);
    }

    /**
     * Store a newly created applicant
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'linkedin_url' => 'nullable|url|max:255',
            'portfolio_url' => 'nullable|url|max:255',
            'work_experience' => 'nullable|string',
            'education' => 'nullable|string',
            'skills' => 'nullable|string',
            'certifications' => 'nullable|string',
            'languages' => 'nullable|string',
            'summary' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // Check if applicant with same email exists
        $existingApplicant = JobApplicant::where('client_identifier', $data['client_identifier'])
            ->where('email', $data['email'])
            ->first();

        if ($existingApplicant) {
            return response()->json([
                'status' => 'error',
                'message' => 'Applicant with this email already exists',
                'data' => $existingApplicant
            ], 409);
        }

        $applicant = JobApplicant::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Applicant created successfully',
            'data' => $applicant
        ], 201);
    }

    /**
     * Display the specified applicant
     */
    public function show(string $id): JsonResponse
    {
        $applicant = JobApplicant::with(['applications.job.jobBoard'])
            ->withCount('applications')
            ->find($id);

        if (!$applicant) {
            return response()->json([
                'status' => 'error',
                'message' => 'Applicant not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $applicant
        ]);
    }

    /**
     * Update the specified applicant
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $applicant = JobApplicant::find($id);

        if (!$applicant) {
            return response()->json([
                'status' => 'error',
                'message' => 'Applicant not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255|unique:job_applicants,email,' . $id,
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'linkedin_url' => 'nullable|url|max:255',
            'portfolio_url' => 'nullable|url|max:255',
            'work_experience' => 'nullable|string',
            'education' => 'nullable|string',
            'skills' => 'nullable|string',
            'certifications' => 'nullable|string',
            'languages' => 'nullable|string',
            'summary' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $applicant->update($validator->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Applicant updated successfully',
            'data' => $applicant->fresh()
        ]);
    }

    /**
     * Remove the specified applicant
     */
    public function destroy(string $id): JsonResponse
    {
        $applicant = JobApplicant::find($id);

        if (!$applicant) {
            return response()->json([
                'status' => 'error',
                'message' => 'Applicant not found'
            ], 404);
        }

        $applicant->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Applicant deleted successfully'
        ]);
    }

    /**
     * Find applicant by email
     */
    public function findByEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string',
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $applicant = JobApplicant::where('client_identifier', $request->client_identifier)
            ->where('email', $request->email)
            ->first();

        if (!$applicant) {
            return response()->json([
                'status' => 'success',
                'data' => null,
                'message' => 'Applicant not found'
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $applicant
        ]);
    }
}
