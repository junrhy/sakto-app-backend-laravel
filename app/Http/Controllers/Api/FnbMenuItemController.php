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
        return fnbMenuItem::all();
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
            'image' => 'nullable|image|max:2048'
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('menu-items', 'public');
            $validated['image'] = Storage::url($path);
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
    public function update(Request $request, fnbMenuItem $fnbMenuItem)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'category' => 'required|string',
            'image' => 'nullable|image|max:2048'
        ]);

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($fnbMenuItem->image) {
                Storage::delete(str_replace('/storage/', 'public/', $fnbMenuItem->image));
            }
            $path = $request->file('image')->store('menu-items', 'public');
            $validated['image'] = Storage::url($path);
        }

        $fnbMenuItem->update($validated);
        return $fnbMenuItem;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(fnbMenuItem $fnbMenuItem)
    {
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
            'ids.*' => 'exists:menu_items,id'
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
}
