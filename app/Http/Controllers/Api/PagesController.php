<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PagesController extends Controller
{
    public function index()
    {
        $pages = Page::latest()->paginate(10);
        return response()->json($pages);
    }

    public function getPages(Request $request)
    {
        $pages = Page::select('id', 'title', 'slug', 'is_published', 'created_at')
            ->where('client_identifier', $request->client_identifier)
            ->latest()
            ->get();
        return response()->json($pages);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:pages,slug',
            'content' => 'required|string',
            'meta_description' => 'nullable|string|max:255',
            'meta_keywords' => 'nullable|string|max:255',
            'is_published' => 'boolean',
            'template' => 'nullable|string|max:255',
            'custom_css' => 'nullable|string',
            'custom_js' => 'nullable|string',
            'featured_image' => 'nullable|url|max:255',
            'client_identifier' => 'required|string|max:255'
        ]);

        $page = Page::create($validated);

        return response()->json([
            'message' => 'Page created successfully',
            'page' => $page
        ], 201);
    }

    public function show($id)
    {
        $page = Page::findOrFail($id);
        return response()->json($page);
    }

    public function update(Request $request, $id)
    {
        try {
            $page = Page::findOrFail($id);

            $rules = [
                'title' => 'required|string|max:255',
                'slug' => ['required', 'string', 'max:255', Rule::unique('pages')->ignore($id)],
                'content' => 'required|string',
                'meta_description' => 'nullable|string|max:255',
                'meta_keywords' => 'nullable|string|max:255',
                'is_published' => 'boolean',
                'template' => 'nullable|string|max:255',
                'custom_css' => 'nullable|string',
                'custom_js' => 'nullable|string',
                'featured_image' => 'nullable|url|max:255',
                'client_identifier' => 'required|string|max:255'
            ];

            $validated = $request->validate($rules);
            
            // Ensure client_identifier matches
            if ($page->client_identifier !== $validated['client_identifier']) {
                return response()->json([
                    'message' => 'Unauthorized: Client identifier mismatch'
                ], 403);
            }

            $page->update($validated);

            return response()->json([
                'message' => 'Page updated successfully',
                'page' => $page
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Page update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Failed to update page',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $page = Page::findOrFail($id);
        $page->delete();

        return response()->json([
            'message' => 'Page deleted successfully'
        ]);
    }

    public function getPage($slug)
    {
        $page = Page::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();
            
        return response()->json($page);
    }

    public function settings()
    {
        $settings = [
            'templates' => [
                'default' => 'Default Template',
                'full-width' => 'Full Width Template',
                'sidebar' => 'Sidebar Template',
            ],
            'meta_fields' => [
                'description' => 'Meta Description',
                'keywords' => 'Meta Keywords',
            ],
        ];

        return response()->json($settings);
    }
} 