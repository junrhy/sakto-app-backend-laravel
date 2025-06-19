<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Content;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class ContentCreatorController extends Controller
{
    public function index(Request $request)
    {
        $query = Content::where('client_identifier', $request->client_identifier);

        // Apply filters
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        $content = $query->latest()->get();
        
        return response()->json([
            'message' => 'Content fetched successfully',
            'data' => $content
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:contents,slug',
            'content' => 'required|string',
            'excerpt' => 'nullable|string|max:500',
            'status' => 'required|string|in:draft,published',
            'featured_image' => 'nullable|string',
            'author' => 'required|string',
            'client_identifier' => 'required|string|max:255',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'tags' => 'nullable|array',
            'categories' => 'nullable|array',
            'scheduled_at' => 'nullable|date',
        ]);

        // Set published_at if status is published
        if ($validated['status'] === 'published') {
            $validated['published_at'] = now();
        }

        $content = Content::create($validated);

        return response()->json([
            'message' => 'Post created successfully',
            'content' => $content
        ], 201);
    }

    public function show($id)
    {
        $content = Content::findOrFail($id);
        return response()->json($content);
    }

    public function update(Request $request, $id)
    {
        try {
            $content = Content::findOrFail($id);

            // Ensure client_identifier matches
            if ($content->client_identifier !== $request->client_identifier) {
                return response()->json([
                    'message' => 'Unauthorized: Client identifier mismatch'
                ], 403);
            }

            $rules = [
                'title' => 'required|string|max:255',
                'slug' => ['required', 'string', 'max:255', Rule::unique('contents')->ignore($id)],
                'content' => 'required|string',
                'excerpt' => 'nullable|string|max:500',
                'status' => 'required|string|in:draft,published',
                'featured_image' => 'nullable|string',
                'author' => 'required|string',
                'client_identifier' => 'required|string|max:255',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string|max:500',
                'tags' => 'nullable|array',
                'categories' => 'nullable|array',
                'scheduled_at' => 'nullable|date',
            ];

            $validated = $request->validate($rules);

            // Set published_at if status is published and wasn't published before
            if ($validated['status'] === 'published' && $content->status !== 'published') {
                $validated['published_at'] = now();
            }

            $content->update($validated);

            return response()->json([
                'message' => 'Post updated successfully',
                'content' => $content
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Content update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Failed to update post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $content = Content::findOrFail($id);
            
            // Delete featured image if exists
            if ($content->featured_image) {
                $path = str_replace('/storage/', '', $content->featured_image);
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
            
            $content->delete();

            return response()->json([
                'message' => 'Post deleted successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Content deletion failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Failed to delete post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:draft,published,archived'
        ]);

        $content = Content::findOrFail($id);
        
        // Set published_at if status is published and wasn't published before
        if ($validated['status'] === 'published' && $content->status !== 'published') {
            $validated['published_at'] = now();
        }

        $content->update($validated);

        return response()->json([
            'message' => 'Post status updated successfully',
            'content' => $content
        ]);
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:contents,id'
        ]);

        $contents = Content::whereIn('id', $validated['ids'])->get();
        
        foreach ($contents as $content) {
            // Delete featured image if exists
            if ($content->featured_image) {
                $path = str_replace('/storage/', '', $content->featured_image);
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
        }

        Content::whereIn('id', $validated['ids'])->delete();

        return response()->json([
            'message' => 'Selected posts deleted successfully'
        ]);
    }

    public function preview($id)
    {
        $content = Content::findOrFail($id);
        return response()->json($content);
    }

    public function settings()
    {
        $settings = [
            'statuses' => [
                'draft' => 'Draft',
                'published' => 'Published',
                'archived' => 'Archived',
            ],
            'image_sizes' => [
                'featured' => '1200x630px',
                'thumbnail' => '300x200px',
            ],
            'meta_fields' => [
                'title' => 'Meta Title',
                'description' => 'Meta Description',
            ],
        ];

        return response()->json($settings);
    }

    public function getContent(Request $request)
    {
        $content = Content::byClient($request->client_identifier)
            ->select('id', 'title', 'slug', 'status', 'created_at', 'author_id')
            ->latest()
            ->get();

        return response()->json($content);
    }
} 