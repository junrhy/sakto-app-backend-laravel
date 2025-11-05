<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Models\JobApplicant;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class JobApplicationController extends Controller
{
    /**
     * Display a listing of applications
     */
    public function index(Request $request): JsonResponse
    {
        $clientIdentifier = $request->query('client_identifier');
        
        if (!$clientIdentifier) {
            return response()->json(['error' => 'Client identifier is required'], 400);
        }

        $query = JobApplication::forClient($clientIdentifier);

        // Apply filters
        if ($request->has('job_id')) {
            $query->byJob($request->job_id);
        }

        if ($request->has('applicant_id')) {
            $query->byApplicant($request->applicant_id);
        }

        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        if ($request->has('date_from')) {
            $query->whereDate('applied_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('applied_at', '<=', $request->date_to);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('applicant', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhereHas('job', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'applied_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $applications = $query->with(['job.jobBoard', 'applicant'])->get();

        return response()->json([
            'status' => 'success',
            'data' => $applications
        ]);
    }

    /**
     * Store a newly created application
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string',
            'job_id' => 'required|exists:job_postings,id',
            'applicant_id' => 'nullable|exists:job_applicants,id',
            'cover_letter' => 'nullable|string',
            'status' => ['nullable', Rule::in(['pending', 'reviewed', 'shortlisted', 'interviewed', 'accepted', 'rejected'])],
            'notes' => 'nullable|string',
            'interview_date' => 'nullable|date',
            // Applicant data (if creating new applicant)
            'applicant_name' => 'required_without:applicant_id|string|max:255',
            'applicant_email' => 'required_without:applicant_id|email|max:255',
            'applicant_phone' => 'nullable|string|max:255',
            'applicant_address' => 'nullable|string',
            'applicant_linkedin_url' => 'nullable|url|max:255',
            'applicant_portfolio_url' => 'nullable|url|max:255',
            'applicant_work_experience' => 'nullable|string',
            'applicant_education' => 'nullable|string',
            'applicant_skills' => 'nullable|string',
            'applicant_certifications' => 'nullable|string',
            'applicant_languages' => 'nullable|string',
            'applicant_summary' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $clientIdentifier = $data['client_identifier'];
        $jobId = $data['job_id'];

        // Check if job exists and belongs to client
        $job = Job::where('id', $jobId)
            ->where('client_identifier', $clientIdentifier)
            ->first();

        if (!$job) {
            return response()->json([
                'status' => 'error',
                'message' => 'Job not found'
            ], 404);
        }

        // Handle applicant
        if (isset($data['applicant_id'])) {
            $applicant = JobApplicant::where('id', $data['applicant_id'])
                ->where('client_identifier', $clientIdentifier)
                ->first();

            if (!$applicant) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Applicant not found'
                ], 404);
            }
        } else {
            // Create or find applicant by email
            $applicantData = [
                'name' => $data['applicant_name'],
                'email' => $data['applicant_email'],
                'phone' => $data['applicant_phone'] ?? null,
                'address' => $data['applicant_address'] ?? null,
                'linkedin_url' => $data['applicant_linkedin_url'] ?? null,
                'portfolio_url' => $data['applicant_portfolio_url'] ?? null,
                'work_experience' => $data['applicant_work_experience'] ?? null,
                'education' => $data['applicant_education'] ?? null,
                'skills' => $data['applicant_skills'] ?? null,
                'certifications' => $data['applicant_certifications'] ?? null,
                'languages' => $data['applicant_languages'] ?? null,
                'summary' => $data['applicant_summary'] ?? null,
            ];

            $applicant = JobApplicant::findOrCreateByEmail(
                $clientIdentifier,
                $data['applicant_email'],
                $applicantData
            );
        }

        // Check if application already exists
        $existingApplication = JobApplication::where('job_id', $jobId)
            ->where('applicant_id', $applicant->id)
            ->first();

        if ($existingApplication) {
            return response()->json([
                'status' => 'error',
                'message' => 'Application already exists for this job',
                'data' => $existingApplication
            ], 409);
        }

        // Create application
        $application = JobApplication::create([
            'client_identifier' => $clientIdentifier,
            'job_id' => $jobId,
            'applicant_id' => $applicant->id,
            'cover_letter' => $data['cover_letter'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'notes' => $data['notes'] ?? null,
            'interview_date' => isset($data['interview_date']) ? $data['interview_date'] : null,
            'applied_at' => now(),
        ]);

        // Increment job applications count
        $job->incrementApplications();

        return response()->json([
            'status' => 'success',
            'message' => 'Application created successfully',
            'data' => $application->load(['job', 'applicant'])
        ], 201);
    }

    /**
     * Display the specified application
     */
    public function show(string $id): JsonResponse
    {
        $application = JobApplication::with(['job.jobBoard', 'applicant'])->find($id);

        if (!$application) {
            return response()->json([
                'status' => 'error',
                'message' => 'Application not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $application
        ]);
    }

    /**
     * Update the specified application
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $application = JobApplication::find($id);

        if (!$application) {
            return response()->json([
                'status' => 'error',
                'message' => 'Application not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'cover_letter' => 'nullable|string',
            'status' => ['nullable', Rule::in(['pending', 'reviewed', 'shortlisted', 'interviewed', 'accepted', 'rejected'])],
            'notes' => 'nullable|string',
            'interview_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // If status is being updated to reviewed, set reviewed_at
        if (isset($data['status']) && $data['status'] === 'reviewed' && !$application->reviewed_at) {
            $data['reviewed_at'] = now();
        }

        $application->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Application updated successfully',
            'data' => $application->fresh()->load(['job', 'applicant'])
        ]);
    }

    /**
     * Remove the specified application
     */
    public function destroy(string $id): JsonResponse
    {
        $application = JobApplication::find($id);

        if (!$application) {
            return response()->json([
                'status' => 'error',
                'message' => 'Application not found'
            ], 404);
        }

        $job = $application->job;
        $application->delete();

        // Decrement job applications count
        if ($job) {
            $job->decrementApplications();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Application deleted successfully'
        ]);
    }

    /**
     * Update application status
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $application = JobApplication::find($id);

        if (!$application) {
            return response()->json([
                'status' => 'error',
                'message' => 'Application not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => ['required', Rule::in(['pending', 'reviewed', 'shortlisted', 'interviewed', 'accepted', 'rejected'])],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $status = $validator->validated()['status'];
        $updateData = ['status' => $status];

        // If status is being updated to reviewed, set reviewed_at
        if ($status === 'reviewed' && !$application->reviewed_at) {
            $updateData['reviewed_at'] = now();
        }

        $application->update($updateData);

        return response()->json([
            'status' => 'success',
            'message' => 'Application status updated successfully',
            'data' => $application->fresh()->load(['job', 'applicant'])
        ]);
    }
}
