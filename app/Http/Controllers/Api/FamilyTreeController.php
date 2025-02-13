<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FamilyMember;
use App\Models\FamilyRelationship;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class FamilyTreeController extends Controller
{
    /**
     * Get all family members for a client
     */
    public function index(Request $request)
    {
        $members = FamilyMember::with(['relationships', 'relatedTo'])
            ->where('client_identifier', $request->user()->identifier)
            ->get();

        return response()->json(['data' => $members]);
    }

    /**
     * Store a new family member
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'birth_date' => 'nullable|date',
            'death_date' => 'nullable|date|after:birth_date',
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'photo' => 'nullable|string|max:2048',
            'notes' => 'nullable|string',
        ]);

        $validated['client_identifier'] = $request->user()->identifier;
        $member = FamilyMember::create($validated);

        return response()->json(['data' => $member], 201);
    }

    /**
     * Get a specific family member
     */
    public function show(Request $request, $id)
    {
        $member = FamilyMember::with(['relationships', 'relatedTo'])
            ->where('client_identifier', $request->user()->identifier)
            ->findOrFail($id);

        return response()->json(['data' => $member]);
    }

    /**
     * Update a family member
     */
    public function update(Request $request, $id)
    {
        $member = FamilyMember::where('client_identifier', $request->user()->identifier)
            ->findOrFail($id);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'birth_date' => 'nullable|date',
            'death_date' => 'nullable|date|after:birth_date',
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'photo' => 'nullable|string|max:2048',
            'notes' => 'nullable|string',
        ]);

        $member->update($validated);

        return response()->json(['data' => $member]);
    }

    /**
     * Delete a family member
     */
    public function destroy(Request $request, $id)
    {
        $member = FamilyMember::where('client_identifier', $request->user()->identifier)
            ->findOrFail($id);

        $member->delete();

        return response()->json(null, 204);
    }

    /**
     * Add a relationship between family members
     */
    public function addRelationship(Request $request)
    {
        $validated = $request->validate([
            'from_member_id' => 'required|exists:family_members,id',
            'to_member_id' => 'required|exists:family_members,id|different:from_member_id',
            'relationship_type' => ['required', Rule::in(['parent', 'child', 'spouse', 'sibling'])],
        ]);

        // Verify both members belong to the same client
        $fromMember = FamilyMember::where('client_identifier', $request->user()->identifier)
            ->findOrFail($validated['from_member_id']);
        
        $toMember = FamilyMember::where('client_identifier', $request->user()->identifier)
            ->findOrFail($validated['to_member_id']);

        $relationship = FamilyRelationship::create($validated);

        // Create reciprocal relationship
        $reciprocalType = $this->getReciprocalRelationshipType($validated['relationship_type']);
        if ($reciprocalType) {
            FamilyRelationship::create([
                'from_member_id' => $validated['to_member_id'],
                'to_member_id' => $validated['from_member_id'],
                'relationship_type' => $reciprocalType
            ]);
        }

        return response()->json(['data' => $relationship], 201);
    }

    /**
     * Remove a relationship between family members
     */
    public function removeRelationship(Request $request)
    {
        $validated = $request->validate([
            'from_member_id' => 'required|exists:family_members,id',
            'to_member_id' => 'required|exists:family_members,id',
        ]);

        // Verify both members belong to the same client
        FamilyMember::where('client_identifier', $request->user()->identifier)
            ->whereIn('id', [$validated['from_member_id'], $validated['to_member_id']])
            ->count() === 2;

        // Delete both direct and reciprocal relationships
        FamilyRelationship::where(function($query) use ($validated) {
            $query->where('from_member_id', $validated['from_member_id'])
                ->where('to_member_id', $validated['to_member_id']);
        })->orWhere(function($query) use ($validated) {
            $query->where('from_member_id', $validated['to_member_id'])
                ->where('to_member_id', $validated['from_member_id']);
        })->delete();

        return response()->json(null, 204);
    }

    /**
     * Export family tree data
     */
    public function export(Request $request)
    {
        $members = FamilyMember::with(['relationships', 'relatedTo'])
            ->where('client_identifier', $request->user()->identifier)
            ->get();

        return response()->json(['data' => $members]);
    }

    /**
     * Import family tree data
     */
    public function import(Request $request)
    {
        $validated = $request->validate([
            'family_members' => 'required|array',
            'family_members.*.first_name' => 'required|string|max:255',
            'family_members.*.last_name' => 'required|string|max:255',
            'family_members.*.birth_date' => 'nullable|date',
            'family_members.*.death_date' => 'nullable|date',
            'family_members.*.gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'family_members.*.relationships' => 'array',
        ]);

        DB::beginTransaction();

        try {
            $clientIdentifier = $request->user()->identifier;
            $memberMap = [];

            // First pass: Create all members
            foreach ($validated['family_members'] as $memberData) {
                $memberData['client_identifier'] = $clientIdentifier;
                $member = FamilyMember::create($memberData);
                $memberMap[$memberData['id']] = $member->id;
            }

            // Second pass: Create relationships
            foreach ($validated['family_members'] as $memberData) {
                if (!empty($memberData['relationships'])) {
                    foreach ($memberData['relationships'] as $relationship) {
                        FamilyRelationship::create([
                            'from_member_id' => $memberMap[$memberData['id']],
                            'to_member_id' => $memberMap[$relationship['to_member_id']],
                            'relationship_type' => $relationship['relationship_type']
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json(['message' => 'Family tree imported successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Family tree import failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to import family tree'], 500);
        }
    }

    /**
     * Get visualization data
     */
    public function getVisualizationData(Request $request)
    {
        $members = FamilyMember::with(['relationships', 'relatedTo'])
            ->where('client_identifier', $request->user()->identifier)
            ->get();

        return response()->json(['data' => $members]);
    }

    /**
     * Get the reciprocal relationship type
     */
    private function getReciprocalRelationshipType($type)
    {
        switch ($type) {
            case 'parent':
                return 'child';
            case 'child':
                return 'parent';
            case 'spouse':
                return 'spouse';
            case 'sibling':
                return 'sibling';
            default:
                return null;
        }
    }
}
