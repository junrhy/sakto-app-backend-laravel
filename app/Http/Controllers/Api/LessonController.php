<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\CourseEnrollment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class LessonController extends Controller
{
    /**
     * Get all lessons for a course.
     */
    public function index(Request $request, $courseId): JsonResponse
    {
        try {
            $course = Course::findOrFail($courseId);
            $lessons = $course->lessons()->orderBy('order_index')->get();

            // Get user's enrollment and progress if contact_id is provided
            $contactId = $request->get('contact_id');
            $enrollment = null;
            $lessonProgress = collect();

            if ($contactId) {
                $enrollment = CourseEnrollment::where('course_id', $courseId)
                    ->where('contact_id', $contactId)
                    ->first();

                if ($enrollment) {
                    $lessonProgress = $enrollment->lessonProgress()
                        ->whereIn('lesson_id', $lessons->pluck('id'))
                        ->get()
                        ->keyBy('lesson_id');
                }
            }

            // Add progress status to each lesson
            $lessonsWithProgress = $lessons->map(function ($lesson) use ($lessonProgress) {
                $progress = $lessonProgress->get($lesson->id);
                $lesson->status = $progress ? $progress->status : 'not_started';
                return $lesson;
            });

            return response()->json([
                'success' => true,
                'data' => $lessonsWithProgress,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch lessons',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a new lesson.
     */
    public function store(Request $request, $courseId): JsonResponse
    {
        try {
            $course = Course::findOrFail($courseId);

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'content' => 'nullable|string',
                'video_url' => 'nullable|url|max:255',
                'duration_minutes' => 'nullable|integer|min:0',
                'order_index' => 'required|integer|min:1',
                'is_free_preview' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();
            $data['course_id'] = $courseId;

            $lesson = Lesson::create($data);

            // Update course lessons count
            $course->updateLessonsCount();

            return response()->json([
                'success' => true,
                'message' => 'Lesson created successfully',
                'data' => $lesson,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create lesson',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific lesson.
     */
    public function show($courseId, $lessonId): JsonResponse
    {
        try {
            $lesson = Lesson::where('course_id', $courseId)
                ->where('id', $lessonId)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $lesson,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lesson not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Update a lesson.
     */
    public function update(Request $request, $courseId, $lessonId): JsonResponse
    {
        try {
            $lesson = Lesson::where('course_id', $courseId)
                ->where('id', $lessonId)
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'content' => 'nullable|string',
                'video_url' => 'nullable|url|max:255',
                'duration_minutes' => 'nullable|integer|min:0',
                'order_index' => 'sometimes|required|integer|min:1',
                'is_free_preview' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();
            $lesson->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Lesson updated successfully',
                'data' => $lesson->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update lesson',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a lesson.
     */
    public function destroy($courseId, $lessonId): JsonResponse
    {
        try {
            $lesson = Lesson::where('course_id', $courseId)
                ->where('id', $lessonId)
                ->firstOrFail();

            $lesson->delete();

            // Update course lessons count
            $course = Course::find($courseId);
            if ($course) {
                $course->updateLessonsCount();
            }

            return response()->json([
                'success' => true,
                'message' => 'Lesson deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete lesson',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reorder lessons.
     */
    public function reorder(Request $request, $courseId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'lesson_orders' => 'required|array',
                'lesson_orders.*.id' => 'required|integer|exists:lessons,id',
                'lesson_orders.*.order' => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();

            foreach ($data['lesson_orders'] as $item) {
                Lesson::where('id', $item['id'])
                    ->where('course_id', $courseId)
                    ->update(['order_index' => $item['order']]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Lessons reordered successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder lessons',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk delete lessons.
     */
    public function bulkDestroy(Request $request, $courseId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'lesson_ids' => 'required|array',
                'lesson_ids.*' => 'integer|exists:lessons,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();

            Lesson::where('course_id', $courseId)
                ->whereIn('id', $data['lesson_ids'])
                ->delete();

            // Update course lessons count
            $course = Course::find($courseId);
            if ($course) {
                $course->updateLessonsCount();
            }

            return response()->json([
                'success' => true,
                'message' => 'Lessons deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete lessons',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
