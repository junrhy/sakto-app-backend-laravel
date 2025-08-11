<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CourseEnrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use Illuminate\Support\Facades\DB;

class LessonProgressController extends Controller
{
    public function index(Request $request, $enrollmentId)
    {
        try {
            $enrollment = CourseEnrollment::findOrFail($enrollmentId);
            $progress = $enrollment->lessonProgress()->with('lesson')->get();

            return response()->json([
                'message' => 'Lesson progress fetched successfully',
                'data' => $progress
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch lesson progress',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($enrollmentId, $lessonId)
    {
        try {
            $progress = LessonProgress::where('course_enrollment_id', $enrollmentId)
                ->where('lesson_id', $lessonId)
                ->with(['lesson', 'enrollment'])
                ->firstOrFail();

            return response()->json([
                'message' => 'Lesson progress fetched successfully',
                'data' => $progress
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Lesson progress not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $enrollmentId, $lessonId)
    {
        try {
            $progress = LessonProgress::where('course_enrollment_id', $enrollmentId)
                ->where('lesson_id', $lessonId)
                ->firstOrFail();

            $validated = $request->validate([
                'status' => 'sometimes|required|in:not_started,in_progress,completed,failed',
                'time_watched_seconds' => 'nullable|integer|min:0',
                'completion_percentage' => 'nullable|integer|min:0|max:100',
                'notes' => 'nullable|string',
            ]);

            $progress->update($validated);

            // Update enrollment progress if lesson is completed
            if (isset($validated['status']) && $validated['status'] === 'completed') {
                $progress->enrollment->updateProgress();
            }

            return response()->json([
                'message' => 'Lesson progress updated successfully',
                'data' => $progress->load(['lesson', 'enrollment'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Lesson progress update failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function markAsStarted($enrollmentId, $lessonId)
    {
        try {
            $progress = LessonProgress::where('course_enrollment_id', $enrollmentId)
                ->where('lesson_id', $lessonId)
                ->firstOrFail();

            $progress->markAsStarted();

            return response()->json([
                'message' => 'Lesson marked as started successfully',
                'data' => $progress->load(['lesson', 'enrollment'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to mark lesson as started',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function markAsCompleted($enrollmentId, $lessonId)
    {
        try {
            $progress = LessonProgress::where('course_enrollment_id', $enrollmentId)
                ->where('lesson_id', $lessonId)
                ->firstOrFail();

            $progress->markAsCompleted();

            return response()->json([
                'message' => 'Lesson marked as completed successfully',
                'data' => $progress->load(['lesson', 'enrollment'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to mark lesson as completed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateVideoProgress(Request $request, $enrollmentId, $lessonId)
    {
        try {
            $progress = LessonProgress::where('course_enrollment_id', $enrollmentId)
                ->where('lesson_id', $lessonId)
                ->firstOrFail();

            $validated = $request->validate([
                'time_watched_seconds' => 'required|integer|min:0',
                'completion_percentage' => 'required|integer|min:0|max:100',
            ]);

            $progress->updateProgress($validated['completion_percentage'], $validated['time_watched_seconds']);

            return response()->json([
                'message' => 'Video progress updated successfully',
                'data' => $progress->load(['lesson', 'enrollment'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Video progress update failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function submitQuiz(Request $request, $enrollmentId, $lessonId)
    {
        try {
            $progress = LessonProgress::where('course_enrollment_id', $enrollmentId)
                ->where('lesson_id', $lessonId)
                ->firstOrFail();

            $validated = $request->validate([
                'answers' => 'required|array',
                'score' => 'required|integer|min:0|max:100',
            ]);

            $progress->submitQuiz($validated['answers'], $validated['score']);

            return response()->json([
                'message' => 'Quiz submitted successfully',
                'data' => [
                    'progress' => $progress->load(['lesson', 'enrollment']),
                    'passed' => $validated['score'] >= 70,
                    'score' => $validated['score'],
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Quiz submission failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getEnrollmentProgress($enrollmentId)
    {
        try {
            $enrollment = CourseEnrollment::with(['course.lessons', 'lessonProgress.lesson'])
                ->findOrFail($enrollmentId);

            $totalLessons = $enrollment->course->lessons->count();
            $completedLessons = $enrollment->lessonProgress->where('status', 'completed')->count();
            $inProgressLessons = $enrollment->lessonProgress->where('status', 'in_progress')->count();
            $notStartedLessons = $enrollment->lessonProgress->where('status', 'not_started')->count();

            $progressData = [
                'enrollment' => $enrollment,
                'total_lessons' => $totalLessons,
                'completed_lessons' => $completedLessons,
                'in_progress_lessons' => $inProgressLessons,
                'not_started_lessons' => $notStartedLessons,
                'progress_percentage' => $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0,
                'estimated_completion_time' => $this->calculateEstimatedCompletionTime($enrollment),
            ];

            return response()->json([
                'message' => 'Enrollment progress fetched successfully',
                'data' => $progressData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch enrollment progress',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getCourseProgress($courseId, $contactId)
    {
        try {
            $enrollment = CourseEnrollment::where('course_id', $courseId)
                ->where('contact_id', $contactId)
                ->with(['course.lessons', 'lessonProgress.lesson'])
                ->first();

            if (!$enrollment) {
                return response()->json([
                    'message' => 'Not enrolled in this course',
                    'data' => null
                ], 404);
            }

            return $this->getEnrollmentProgress($enrollment->id);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch course progress',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function calculateEstimatedCompletionTime($enrollment)
    {
        $remainingLessons = $enrollment->course->lessons->count() - $enrollment->lessonProgress->where('status', 'completed')->count();
        $averageTimePerLesson = $enrollment->course->lessons->avg('duration_minutes') ?: 30; // Default 30 minutes
        
        $estimatedMinutes = $remainingLessons * $averageTimePerLesson;
        
        if ($estimatedMinutes < 60) {
            return $estimatedMinutes . ' minutes';
        } else {
            $hours = floor($estimatedMinutes / 60);
            $minutes = $estimatedMinutes % 60;
            return $hours . 'h ' . $minutes . 'm';
        }
    }
}
