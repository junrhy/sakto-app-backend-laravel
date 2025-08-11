<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\LessonProgress;
use Illuminate\Support\Facades\DB;

class CourseEnrollmentController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = CourseEnrollment::with(['course', 'lessonProgress']);

            // Filter by client
            if ($request->has('client_identifier')) {
                $query->byClient($request->client_identifier);
            }

            // Filter by course
            if ($request->has('course_id')) {
                $query->where('course_id', $request->course_id);
            }

            // Filter by contact
            if ($request->has('contact_id')) {
                $query->byContact($request->contact_id);
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by payment status
            if ($request->has('payment_status')) {
                $query->where('payment_status', $request->payment_status);
            }

            $enrollments = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));

            return response()->json([
                'message' => 'Enrollments fetched successfully',
                'data' => $enrollments
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch enrollments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'course_id' => 'required|integer|exists:courses,id',
                'contact_id' => 'required|string',
                'student_name' => 'required|string|max:255',
                'student_email' => 'required|email',
                'student_phone' => 'nullable|string',
                'payment_method' => 'nullable|string',
                'payment_reference' => 'nullable|string',
                'amount_paid' => 'nullable|numeric|min:0',
                'client_identifier' => 'required|string',
            ]);

            $course = Course::findOrFail($validated['course_id']);

            // Check if already enrolled
            $existingEnrollment = CourseEnrollment::where('course_id', $validated['course_id'])
                ->where('contact_id', $validated['contact_id'])
                ->where('status', '!=', 'cancelled')
                ->first();

            if ($existingEnrollment) {
                return response()->json([
                    'message' => 'Already enrolled in this course',
                    'data' => $existingEnrollment
                ], 400);
            }

            // Set payment status based on course type
            $validated['payment_status'] = $course->is_free ? 'paid' : 'pending';
            $validated['amount_paid'] = $course->is_free ? 0 : ($validated['amount_paid'] ?? 0);

            $enrollment = CourseEnrollment::create($validated);

            // Create lesson progress records for all lessons
            $lessons = $course->lessons;
            foreach ($lessons as $lesson) {
                LessonProgress::create([
                    'course_enrollment_id' => $enrollment->id,
                    'lesson_id' => $lesson->id,
                    'status' => 'not_started',
                ]);
            }

            // Update course enrollment count
            $course->updateEnrolledCount();

            return response()->json([
                'message' => 'Enrollment created successfully',
                'data' => $enrollment->load(['course', 'lessonProgress'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Enrollment creation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $enrollment = CourseEnrollment::with(['course.lessons', 'lessonProgress.lesson'])
                ->findOrFail($id);

            return response()->json([
                'message' => 'Enrollment fetched successfully',
                'data' => $enrollment
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Enrollment not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $enrollment = CourseEnrollment::findOrFail($id);

            $validated = $request->validate([
                'status' => 'sometimes|required|in:active,completed,cancelled,expired',
                'payment_status' => 'sometimes|required|in:pending,paid,failed,refunded',
                'amount_paid' => 'nullable|numeric|min:0',
                'payment_method' => 'nullable|string',
                'payment_reference' => 'nullable|string',
                'completed_at' => 'nullable|date',
                'expires_at' => 'nullable|date',
            ]);

            $enrollment->update($validated);

            return response()->json([
                'message' => 'Enrollment updated successfully',
                'data' => $enrollment->load(['course', 'lessonProgress'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Enrollment update failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $enrollment = CourseEnrollment::findOrFail($id);
            $course = $enrollment->course;
            
            $enrollment->delete();

            // Update course enrollment count
            $course->updateEnrolledCount();

            return response()->json([
                'message' => 'Enrollment deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Enrollment deletion failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function processPayment(Request $request, $id)
    {
        try {
            $enrollment = CourseEnrollment::findOrFail($id);

            $validated = $request->validate([
                'payment_method' => 'required|string',
                'payment_reference' => 'required|string',
                'amount_paid' => 'required|numeric|min:0',
            ]);

            $enrollment->update([
                'payment_status' => 'paid',
                'payment_method' => $validated['payment_method'],
                'payment_reference' => $validated['payment_reference'],
                'amount_paid' => $validated['amount_paid'],
            ]);

            return response()->json([
                'message' => 'Payment processed successfully',
                'data' => $enrollment
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Payment processing failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function generateCertificate($id)
    {
        try {
            $enrollment = CourseEnrollment::with('course')->findOrFail($id);

            if ($enrollment->status !== 'completed') {
                return response()->json([
                    'message' => 'Course must be completed to generate certificate'
                ], 400);
            }

            $certificateData = $enrollment->generateCertificate();

            return response()->json([
                'message' => 'Certificate generated successfully',
                'data' => $certificateData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Certificate generation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getStatistics(Request $request)
    {
        try {
            $query = CourseEnrollment::query();

            if ($request->has('client_identifier')) {
                $query->byClient($request->client_identifier);
            }

            if ($request->has('course_id')) {
                $query->where('course_id', $request->course_id);
            }

            $statistics = [
                'total_enrollments' => $query->count(),
                'active_enrollments' => $query->where('status', 'active')->count(),
                'completed_enrollments' => $query->where('status', 'completed')->count(),
                'cancelled_enrollments' => $query->where('status', 'cancelled')->count(),
                'paid_enrollments' => $query->where('payment_status', 'paid')->count(),
                'pending_payments' => $query->where('payment_status', 'pending')->count(),
                'total_revenue' => $query->where('payment_status', 'paid')->sum('amount_paid'),
                'average_progress' => $query->where('status', 'active')->avg('progress_percentage'),
            ];

            return response()->json([
                'message' => 'Statistics fetched successfully',
                'data' => $statistics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
