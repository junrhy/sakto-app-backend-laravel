<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseEnrollment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    /**
     * Get all courses with filtering and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Course::query();

            // Filter by client identifier
            if ($request->has('client_identifier')) {
                $query->byClient($request->client_identifier);
            }

            // Filter by category
            if ($request->filled('category')) {
                $query->byCategory($request->category);
            }

            // Filter by difficulty
            if ($request->filled('difficulty')) {
                $query->byDifficulty($request->difficulty);
            }

            // Filter by status
            if ($request->filled('status')) {
                $query->byStatus($request->status);
            }

            // Filter by featured
            if ($request->has('featured')) {
                $query->featured();
            }

            // Filter by free/paid
            if ($request->has('is_free')) {
                if ($request->boolean('is_free')) {
                    $query->free();
                } else {
                    $query->paid();
                }
            }

            // Search by title or description
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('instructor_name', 'like', "%{$search}%");
                });
            }

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginate
            $perPage = $request->get('per_page', 15);
            $courses = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $courses->items(),
                'pagination' => [
                    'current_page' => $courses->currentPage(),
                    'last_page' => $courses->lastPage(),
                    'per_page' => $courses->perPage(),
                    'total' => $courses->total(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch courses',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a new course.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'slug' => 'nullable|string|max:255|unique:courses,slug',
                'thumbnail_url' => 'nullable|url|max:255',
                'video_url' => 'nullable|url|max:255',
                'difficulty' => 'required|in:beginner,intermediate,advanced',
                'status' => 'required|in:draft,published,archived',
                'is_featured' => 'boolean',
                'is_free' => 'boolean',
                'price' => 'nullable|numeric|min:0',
                'currency' => 'nullable|string|max:3',
                'duration_minutes' => 'nullable|integer|min:0',
                'tags' => 'nullable|array',
                'tags.*' => 'string',
                'requirements' => 'nullable|array',
                'requirements.*' => 'string',
                'learning_outcomes' => 'nullable|array',
                'learning_outcomes.*' => 'string',
                'instructor_name' => 'nullable|string|max:255',
                'instructor_bio' => 'nullable|string',
                'instructor_avatar' => 'nullable|url|max:255',
                'category' => 'nullable|string|max:255',
                'subcategory' => 'nullable|string|max:255',
                'client_identifier' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();

            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['title']);
            }

            // Ensure slug is unique
            $originalSlug = $data['slug'];
            $counter = 1;
            while (Course::where('slug', $data['slug'])->exists()) {
                $data['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }

            $course = Course::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Course created successfully',
                'data' => $course,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create course',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific course.
     */
    public function show($id): JsonResponse
    {
        try {
            $course = Course::with(['enrollments', 'lessons'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $course,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Course not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Update a course.
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $course = Course::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|required|string',
                'slug' => 'nullable|string|max:255|unique:courses,slug,' . $id,
                'thumbnail_url' => 'nullable|url|max:255',
                'video_url' => 'nullable|url|max:255',
                'difficulty' => 'sometimes|required|in:beginner,intermediate,advanced',
                'status' => 'sometimes|required|in:draft,published,archived',
                'is_featured' => 'boolean',
                'is_free' => 'boolean',
                'price' => 'nullable|numeric|min:0',
                'currency' => 'nullable|string|max:3',
                'duration_minutes' => 'nullable|integer|min:0',
                'tags' => 'nullable|array',
                'tags.*' => 'string',
                'requirements' => 'nullable|array',
                'requirements.*' => 'string',
                'learning_outcomes' => 'nullable|array',
                'learning_outcomes.*' => 'string',
                'instructor_name' => 'nullable|string|max:255',
                'instructor_bio' => 'nullable|string',
                'instructor_avatar' => 'nullable|url|max:255',
                'category' => 'nullable|string|max:255',
                'subcategory' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();

            // Generate slug if not provided and title changed
            if (empty($data['slug']) && isset($data['title'])) {
                $data['slug'] = Str::slug($data['title']);
            }

            // Ensure slug is unique
            if (isset($data['slug'])) {
                $originalSlug = $data['slug'];
                $counter = 1;
                while (Course::where('slug', $data['slug'])->where('id', '!=', $id)->exists()) {
                    $data['slug'] = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }

            $course->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Course updated successfully',
                'data' => $course->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update course',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a course.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $course = Course::findOrFail($id);
            $course->delete();

            return response()->json([
                'success' => true,
                'message' => 'Course deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete course',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get course categories.
     */
    public function categories(Request $request): JsonResponse
    {
        try {
            $query = Course::query();

            // Filter by client identifier
            if ($request->has('client_identifier')) {
                $query->byClient($request->client_identifier);
            }

            $categories = $query->whereNotNull('category')
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
     * Get course progress for a specific contact.
     */
    public function getProgress($courseId, $contactId): JsonResponse
    {
        try {
            $enrollment = CourseEnrollment::where('course_id', $courseId)
                ->where('contact_id', $contactId)
                ->with(['course', 'lessonProgress'])
                ->first();

            if (!$enrollment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Enrollment not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $enrollment,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch progress',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get course statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $query = Course::query();

            // Filter by client identifier
            if ($request->has('client_identifier')) {
                $query->byClient($request->client_identifier);
            }

            $statistics = [
                'total_courses' => $query->count(),
                'published_courses' => $query->clone()->byStatus('published')->count(),
                'draft_courses' => $query->clone()->byStatus('draft')->count(),
                'archived_courses' => $query->clone()->byStatus('archived')->count(),
                'featured_courses' => $query->clone()->featured()->count(),
                'free_courses' => $query->clone()->free()->count(),
                'paid_courses' => $query->clone()->paid()->count(),
                'total_enrollments' => CourseEnrollment::when($request->has('client_identifier'), function ($q) use ($request) {
                    $q->byClient($request->client_identifier);
                })->count(),
                'active_enrollments' => CourseEnrollment::when($request->has('client_identifier'), function ($q) use ($request) {
                    $q->byClient($request->client_identifier);
                })->byStatus('active')->count(),
                'completed_enrollments' => CourseEnrollment::when($request->has('client_identifier'), function ($q) use ($request) {
                    $q->byClient($request->client_identifier);
                })->byStatus('completed')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $statistics,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk update course status.
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'course_ids' => 'required|array',
                'course_ids.*' => 'integer|exists:courses,id',
                'status' => 'required|in:draft,published,archived',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();

            Course::whereIn('id', $data['course_ids'])->update([
                'status' => $data['status']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Courses status updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update courses status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk delete courses.
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'course_ids' => 'required|array',
                'course_ids.*' => 'integer|exists:courses,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();

            Course::whereIn('id', $data['course_ids'])->delete();

            return response()->json([
                'success' => true,
                'message' => 'Courses deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete courses',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
