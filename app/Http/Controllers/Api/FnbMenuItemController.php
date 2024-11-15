<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\fnbMenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FnbMenuItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $fnbMenuItems = fnbMenuItem::all()->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'price' => $item->price,
                'category' => $item->category,
                'image' => $item->image ?? 'https://via.placeholder.com/150',
                'client_identifier' => $item->client_identifier
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => 'FNB Menu Items retrieved successfully',
            'data' => [
                'fnb_menu_items' => $fnbMenuItems
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'category' => 'required|string',
            'client_identifier' => 'nullable|string'
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('fnb-menu-items', 'public');
            $validated['image'] = Storage::url($path);
            $validated['public_image_url'] = 'http://127.0.0.1:8001/image/fnb-menu-item/' . str_replace('fnb-menu-items/', '', $path);
        }
    
        return fnbMenuItem::create($validated);
    }

    /**
     * Display the specified resource.
     */
    public function show(fnbMenuItem $fnbMenuItem)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(fnbMenuItem $fnbMenuItem)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'category' => 'required|string',
            'client_identifier' => 'nullable|string'
        ]);

        $fnbMenuItem = fnbMenuItem::find($request->id);

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($fnbMenuItem->image) {
                Storage::delete(str_replace('/storage/', 'public/', $fnbMenuItem->image));
            }
            $path = $request->file('image')->store('menu-items', 'public');
            $validated['image'] = Storage::url($path);
        }

        $fnbMenuItem->update($validated);
        return response()->json(['status' => 'success', 'message' => 'Menu item updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $fnbMenuItem = fnbMenuItem::find($request->id);

        if ($fnbMenuItem->image) {
            Storage::delete(str_replace('/storage/', 'public/', $fnbMenuItem->image));
        }
        $fnbMenuItem->delete();
        return response()->noContent();
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:fnb_menu_items,id'
        ]);

        $fnbMenuItems = fnbMenuItem::whereIn('id', $validated['ids'])->get();
        
        foreach ($fnbMenuItems as $fnbMenuItem) {
            if ($fnbMenuItem->image) {
                Storage::delete(str_replace('/storage/', 'public/', $fnbMenuItem->image));
            }
        }

        fnbMenuItem::whereIn('id', $validated['ids'])->delete();
        return response()->noContent();
    }

    public function getImage($filename)
    {
        $image = Storage::disk('public')->get('fnb-menu-items/' . $filename);
        return response($image, 200)->header('Content-Type', 'image/jpeg');
    }
}
