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
use Illuminate\Support\Facades\Hash;
use App\Models\ClientDetails;
use App\Models\Client;
use App\Models\FamilyMemberEditRequest;

class FamilyTreeController extends Controller
{
    /**
     * Get all family members for a client
     */
    public function index(Request $request)
    {
        $members = FamilyMember::with([
            'relationships',
            'relationships.toMember:id,first_name,last_name,birth_date,death_date,photo',
            'relatedTo',
            'relatedTo.fromMember:id,first_name,last_name,birth_date,death_date,photo'
        ])
            ->where('client_identifier', $request->client_identifier)
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
            'photo' => 'nullable|string',
            'notes' => 'nullable|string',
            'client_identifier' => 'required|string',
        ]);

        $member = FamilyMember::create($validated);
        return response()->json(['data' => $member], 201);
    }

    /**
     * Get a specific family member
     */
    public function show(Request $request, $id)
    {
        $member = FamilyMember::with([
            'relationships',
            'relationships.toMember:id,first_name,last_name,birth_date,death_date,photo',
            'relatedTo',
            'relatedTo.fromMember:id,first_name,last_name,birth_date,death_date,photo'
        ])
            ->where('client_identifier', $request->client_identifier)
            ->findOrFail($id);

        return response()->json(['data' => $member]);
    }

    /**
     * Update a family member
     */
    public function update(Request $request, $id)
    {
        $member = FamilyMember::where('client_identifier', $request->client_identifier)
            ->findOrFail($id);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'birth_date' => 'nullable|date',
            'death_date' => 'nullable|date|after:birth_date',
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'photo' => 'nullable|string',
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
        $member = FamilyMember::where('client_identifier', $request->client_identifier)
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
        $fromMember = FamilyMember::where('client_identifier', $request->client_identifier)
            ->findOrFail($validated['from_member_id']);

        $toMember = FamilyMember::where('client_identifier', $request->client_identifier)
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
            'client_identifier' => 'required|string',
        ]);

        // Verify both members belong to the same client
        FamilyMember::where('client_identifier', $validated['client_identifier'])
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
            ->where('client_identifier', $request->client_identifier)
            ->get();

        $exportData = [
            'version' => '1.0',
            'exportDate' => now()->toISOString(),
            'metadata' => [
                'totalMembers' => $members->count(),
                'exportedBy' => $request->user()->name ?? 'Unknown',
                'clientIdentifier' => $request->client_identifier
            ],
            'familyMembers' => $members->map(function ($member) {
                return [
                    'id' => $member->id,
                    'first_name' => $member->first_name,
                    'last_name' => $member->last_name,
                    'birth_date' => $member->birth_date,
                    'death_date' => $member->death_date,
                    'gender' => $member->gender,
                    'photo' => $member->photo,
                    'notes' => $member->notes,
                    'relationships' => $member->relationships->map(function ($rel) {
                        return [
                            'id' => $rel->id,
                            'to_member_id' => $rel->to_member_id,
                            'relationship_type' => $rel->relationship_type
                        ];
                    })
                ];
            })
        ];

        return response()->json(['data' => $exportData]);
    }

    /**
     * Import family tree data
     */
    public function import(Request $request)
    {
        $validated = $request->validate([
            'family_members' => 'required|array',
            'family_members.*.import_id' => 'required',
            'family_members.*.first_name' => 'required|string|max:255',
            'family_members.*.last_name' => 'required|string|max:255',
            'family_members.*.birth_date' => 'nullable|date',
            'family_members.*.death_date' => 'nullable|date',
            'family_members.*.gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'family_members.*.relationships' => 'array',
            'family_members.*.relationships.*.import_id' => 'nullable',
            'family_members.*.relationships.*.to_member_import_id' => 'nullable',
            'family_members.*.relationships.*.relationship_type' => ['nullable', Rule::in(['parent', 'child', 'spouse', 'sibling'])],
            'import_mode' => ['required', Rule::in(['skip', 'update', 'duplicate'])],
            'client_identifier' => 'required|string'
        ]);

        DB::beginTransaction();

        try {
            Log::info('Starting family tree import', [
                'mode' => $validated['import_mode'],
                'members_count' => count($validated['family_members'])
            ]);

            $clientIdentifier = $validated['client_identifier'];
            $memberMap = [];
            $importMode = $validated['import_mode'];
            $stats = [
                'total' => count($validated['family_members']),
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'relationships_created' => 0
            ];

            // First pass: Process members based on import mode
            foreach ($validated['family_members'] as $memberData) {
                Log::info('Processing member', [
                    'name' => $memberData['first_name'] . ' ' . $memberData['last_name'],
                    'import_id' => $memberData['import_id']
                ]);

                // Clean up the data
                $cleanMemberData = [
                    'first_name' => $memberData['first_name'],
                    'last_name' => $memberData['last_name'],
                    'birth_date' => $memberData['birth_date'],
                    'death_date' => $memberData['death_date'] ?? null,
                    'gender' => $memberData['gender'],
                    'notes' => $memberData['notes'] ?? null,
                    'photo' => $memberData['photo'] ?? null,
                    'client_identifier' => $clientIdentifier
                ];

                $existingMember = FamilyMember::where('client_identifier', $clientIdentifier)
                    ->where('first_name', $memberData['first_name'])
                    ->where('last_name', $memberData['last_name'])
                    ->where('birth_date', $memberData['birth_date'])
                    ->first();

                if ($existingMember) {
                    Log::info('Found existing member', ['id' => $existingMember->id]);
                    
                    switch ($importMode) {
                        case 'skip':
                            $memberMap[$memberData['import_id']] = $existingMember->id;
                            $stats['skipped']++;
                            Log::info('Skipping existing member');
                            continue 2;
                        
                        case 'update':
                            $existingMember->update($cleanMemberData);
                            $memberMap[$memberData['import_id']] = $existingMember->id;
                            $stats['updated']++;
                            Log::info('Updated existing member');
                            continue 2;
                        
                        case 'duplicate':
                            // Fall through to create new member
                            break;
                    }
                }

                // Create new member
                $member = FamilyMember::create($cleanMemberData);
                $memberMap[$memberData['import_id']] = $member->id;
                $stats['created']++;
                Log::info('Created new member', ['new_id' => $member->id]);
            }

            // Second pass: Create relationships
            foreach ($validated['family_members'] as $memberData) {
                if (!empty($memberData['relationships'])) {
                    foreach ($memberData['relationships'] as $relationship) {
                        // Skip if either member wasn't imported
                        if (!isset($memberMap[$memberData['import_id']]) || !isset($memberMap[$relationship['to_member_import_id']])) {
                            Log::warning('Skipping relationship - member not found', [
                                'from_import_id' => $memberData['import_id'],
                                'to_import_id' => $relationship['to_member_import_id']
                            ]);
                            continue;
                        }

                        $newFromId = $memberMap[$memberData['import_id']];
                        $newToId = $memberMap[$relationship['to_member_import_id']];

                        // Check if relationship already exists
                        $existingRelationship = FamilyRelationship::where('from_member_id', $newFromId)
                            ->where('to_member_id', $newToId)
                            ->first();

                        if (!$existingRelationship) {
                            Log::info('Creating relationship', [
                                'from_id' => $newFromId,
                                'to_id' => $newToId,
                                'type' => $relationship['relationship_type']
                            ]);

                            FamilyRelationship::create([
                                'from_member_id' => $newFromId,
                                'to_member_id' => $newToId,
                                'relationship_type' => $relationship['relationship_type']
                            ]);

                            // Create reciprocal relationship if needed
                            $reciprocalType = $this->getReciprocalRelationshipType($relationship['relationship_type']);
                            if ($reciprocalType) {
                                FamilyRelationship::create([
                                    'from_member_id' => $newToId,
                                    'to_member_id' => $newFromId,
                                    'relationship_type' => $reciprocalType
                                ]);
                            }
                            $stats['relationships_created']++;
                        } else {
                            Log::info('Relationship already exists', [
                                'from_id' => $newFromId,
                                'to_id' => $newToId
                            ]);
                        }
                    }
                }
            }

            DB::commit();
            Log::info('Import completed successfully', $stats);

            return response()->json([
                'message' => 'Family tree imported successfully',
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Family tree import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Get visualization data
     */
    public function getVisualizationData(Request $request)
    {
        $members = FamilyMember::with(['relationships', 'relatedTo'])
            ->where('client_identifier', $request->client_identifier)
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

    /**
     * Get family tree settings
     */
    public function settings(Request $request)
    {
        try {
            // Initialize default settings structure
            $formattedSettings = [
                'organization_info' => [
                    'family_name' => '',
                    'email' => '',
                    'contact_number' => '',
                    'website' => '',
                    'address' => '',
                    'banner' => '',
                    'logo' => ''
                ],
                'auth' => [
                    'username' => '',
                    'password' => ''
                ],
                'elected_officials' => []
            ];

            // Get settings from database
            $settings = ClientDetails::where('client_identifier', $request->client_identifier)
                ->where('app_name', 'family-tree')
                ->get();
            
            // Process settings if any exist
            foreach ($settings as $setting) {
                $parts = explode('.', $setting->name);
                $section = $parts[0];
                
                if ($section === 'elected_officials') {
                    $formattedSettings[$section] = json_decode($setting->value, true) ?? [];
                } elseif (count($parts) > 1) {
                    $key = $parts[1];
                    $formattedSettings[$section][$key] = $setting->value;
                }
            }

            return response()->json([
                'data' => $formattedSettings
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching family tree settings: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to fetch settings',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save family tree settings
     */
    public function saveSettings(Request $request)
    {
        try {
            $validated = $request->validate([
                'organization_info' => 'required|array',
                'organization_info.family_name' => 'required|string|max:255',
                'organization_info.email' => 'nullable|email|max:255',
                'organization_info.contact_number' => 'nullable|string|max:50',
                'organization_info.website' => 'nullable|url|max:255',
                'organization_info.address' => 'nullable|string|max:500',
                'organization_info.banner' => 'nullable|string|max:1000',
                'organization_info.logo' => 'nullable|string|max:1000',
                'auth' => 'required|array',
                'auth.username' => 'nullable|string|max:255',
                'auth.password' => 'nullable|string|min:6',
                'elected_officials' => 'nullable|array',
                'elected_officials.*.name' => 'required_with:elected_officials|string|max:255',
                'elected_officials.*.officials' => 'required_with:elected_officials|array',
                'elected_officials.*.officials.*.name' => 'required_with:elected_officials.*.officials|string|max:255',
                'elected_officials.*.officials.*.position' => 'required_with:elected_officials.*.officials|string|max:255',
                'elected_officials.*.officials.*.profile_url' => 'nullable|string'
            ]);

            // Check if client exists
            $client = Client::where('client_identifier', $request->client_identifier)->first();
            
            if (!$client) {
                return response()->json([
                    'error' => 'Client not found',
                    'message' => 'No client found with the provided identifier'
                ], 404);
            }

            DB::beginTransaction();

            try {
                // Process organization info
                foreach ($validated['organization_info'] as $key => $value) {
                    ClientDetails::updateOrCreate(
                        [
                            'app_name' => 'family-tree',
                            'client_id' => $client->id,
                            'client_identifier' => $client->client_identifier,
                            'name' => "organization_info.{$key}"
                        ],
                        [
                            'value' => $value ?? ''
                        ]
                    );
                }

                // Process auth settings
                foreach ($validated['auth'] as $key => $value) {
                    if ($key === 'password' && !empty($value)) {
                        $value = Hash::make($value);
                    } elseif ($key === 'password') {
                        // If no new password provided, skip updating it
                        continue;
                    }
                    
                    ClientDetails::updateOrCreate(
                        [
                            'app_name' => 'family-tree',
                            'client_id' => $client->id,
                            'client_identifier' => $client->client_identifier,
                            'name' => "auth.{$key}"
                        ],
                        [
                            'value' => $value ?? ''
                        ]
                    );
                }

                // Delete existing elected officials record
                ClientDetails::where([
                    'app_name' => 'family-tree',
                    'client_id' => $client->id,
                    'client_identifier' => $client->client_identifier,
                    'name' => 'elected_officials'
                ])->delete();

                // Create new elected officials record if data is provided
                if (!empty($validated['elected_officials'])) {
                    ClientDetails::create([
                        'app_name' => 'family-tree',
                        'client_id' => $client->id,
                        'client_identifier' => $client->client_identifier,
                        'name' => 'elected_officials',
                        'value' => json_encode($validated['elected_officials'])
                    ]);
                }

                DB::commit();

                // Return updated settings
                return $this->settings($request);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Error saving family tree settings: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to save settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all pending edit requests for a client
     */
    public function getEditRequests(Request $request)
    {
        $editRequests = FamilyMemberEditRequest::where('client_identifier', $request->client_identifier)
            ->where('status', 'pending')
            ->with(['member'])
            ->get();

        return response()->json([
            'data' => $editRequests
        ]);
    }

    /**
     * Accept an edit request
     */
    public function acceptEditRequest(Request $request, $id)
    {
        $editRequest = FamilyMemberEditRequest::where('client_identifier', $request->client_identifier)
            ->where('id', $id)
            ->where('status', 'pending')
            ->firstOrFail();

        try {
            DB::beginTransaction();

            // Update the family member with the proposed changes
            $member = $editRequest->member;
            $member->update([
                'first_name' => $editRequest->first_name,
                'last_name' => $editRequest->last_name,
                'birth_date' => $editRequest->birth_date,
                'death_date' => $editRequest->death_date,
                'gender' => $editRequest->gender,
                'notes' => $editRequest->notes,
                'photo' => $editRequest->photo
            ]);

            // Update the edit request status
            $editRequest->update(['status' => 'accepted']);

            DB::commit();

            return response()->json([
                'message' => 'Edit request accepted successfully',
                'data' => $member
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to accept edit request', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Failed to accept edit request'
            ], 500);
        }
    }

    /**
     * Reject an edit request
     */
    public function rejectEditRequest(Request $request, $id)
    {
        $editRequest = FamilyMemberEditRequest::where('client_identifier', $request->client_identifier)
            ->where('id', $id)
            ->where('status', 'pending')
            ->firstOrFail();

        try {
            DB::beginTransaction();

            // Delete the edit request record
            $editRequest->delete();

            DB::commit();

            return response()->json([
                'message' => 'Edit request rejected and deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reject edit request', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Failed to reject edit request'
            ], 500);
        }
    }

    /**
     * Create a new edit request for a family member
     */
    public function editRequests(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:family_members,id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'birth_date' => 'required|date',
            'death_date' => 'nullable|date|after:birth_date',
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'photo' => 'nullable|string',
            'notes' => 'nullable|string',
            'client_identifier' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            // Verify the member belongs to the client
            $member = FamilyMember::where('client_identifier', $validated['client_identifier'])
                ->findOrFail($validated['member_id']);

            // Create the edit request
            $editRequest = FamilyMemberEditRequest::create([
                'member_id' => $validated['member_id'],
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'birth_date' => $validated['birth_date'],
                'death_date' => $validated['death_date'],
                'gender' => $validated['gender'],
                'photo' => $validated['photo'],
                'notes' => $validated['notes'],
                'client_identifier' => $validated['client_identifier'],
                'status' => 'pending'
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Edit request created successfully',
                'data' => $editRequest
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create edit request', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Failed to create edit request'
            ], 500);
        }
    }
}
