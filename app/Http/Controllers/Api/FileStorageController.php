<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FileStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FileStorageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $clientIdentifier = $request->input('client_identifier');
            
            $query = FileStorage::where('client_identifier', $clientIdentifier);
            
            // Filter by folder
            if ($request->has('folder')) {
                $folder = $request->input('folder');
                if ($folder === 'null' || $folder === '' || $folder === 'none') {
                    $query->where(function($q) {
                        $q->whereNull('folder')->orWhere('folder', '');
                    });
                } else {
                    $query->where('folder', $folder);
                }
            }
            
            // Filter by file type
            if ($request->has('file_type')) {
                $query->where('file_type', $request->input('file_type'));
            }
            
            // Search by name or description
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('original_name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }
            
            // Sort
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);
            
            $files = $query->get();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Files retrieved successfully',
                'data' => $files
            ]);
        } catch (\Exception $e) {
            Log::error('FileStorageController@index error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve files',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'client_identifier' => 'required|string',
                'name' => 'required|string|max:255',
                'original_name' => 'required|string|max:255',
                'file_url' => 'required|url',
                'mime_type' => 'nullable|string',
                'file_size' => 'nullable|string',
                'file_type' => 'nullable|string|in:image,document,video,audio,other',
                'description' => 'nullable|string',
                'folder' => 'nullable|string|max:255',
                'tags' => 'nullable|array',
            ]);
            
            // Normalize empty folder string to null
            if (isset($validated['folder']) && $validated['folder'] === '') {
                $validated['folder'] = null;
            }
            
            // Auto-detect file type from mime type if not provided
            if (empty($validated['file_type']) && !empty($validated['mime_type'])) {
                $validated['file_type'] = FileStorage::getFileTypeFromMime($validated['mime_type']);
            }
            
            $fileStorage = FileStorage::create($validated);
            
            return response()->json([
                'status' => 'success',
                'message' => 'File stored successfully',
                'data' => $fileStorage
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('FileStorageController@store error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to store file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        try {
            $clientIdentifier = $request->input('client_identifier');
            
            $file = FileStorage::where('id', $id)
                ->where('client_identifier', $clientIdentifier)
                ->first();
            
            if (!$file) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'File not found'
                ], 404);
            }
            
            // Increment download count
            $file->increment('download_count');
            
            return response()->json([
                'status' => 'success',
                'message' => 'File retrieved successfully',
                'data' => $file
            ]);
        } catch (\Exception $e) {
            Log::error('FileStorageController@show error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $clientIdentifier = $request->input('client_identifier');
            
            $file = FileStorage::where('id', $id)
                ->where('client_identifier', $clientIdentifier)
                ->first();
            
            if (!$file) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'File not found'
                ], 404);
            }
            
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'folder' => 'nullable|string|max:255',
                'tags' => 'nullable|array',
            ]);
            
            // Normalize empty folder string to null
            if (isset($validated['folder']) && $validated['folder'] === '') {
                $validated['folder'] = null;
            }
            
            $file->update($validated);
            
            return response()->json([
                'status' => 'success',
                'message' => 'File updated successfully',
                'data' => $file
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('FileStorageController@update error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        try {
            $clientIdentifier = $request->input('client_identifier');
            
            $file = FileStorage::where('id', $id)
                ->where('client_identifier', $clientIdentifier)
                ->first();
            
            if (!$file) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'File not found'
                ], 404);
            }
            
            // Note: File deletion from storage is handled in frontend
            // Backend only removes the database record
            $file->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'File deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('FileStorageController@destroy error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get folders list
     */
    public function getFolders(Request $request)
    {
        try {
            $clientIdentifier = $request->input('client_identifier');
            
            $folders = FileStorage::where('client_identifier', $clientIdentifier)
                ->whereNotNull('folder')
                ->where('folder', '!=', '')
                ->distinct()
                ->pluck('folder')
                ->sort()
                ->values();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Folders retrieved successfully',
                'data' => $folders
            ]);
        } catch (\Exception $e) {
            Log::error('FileStorageController@getFolders error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve folders',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
