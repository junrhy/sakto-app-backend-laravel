<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MortuaryMember;
use App\Models\MortuaryContribution;
use App\Models\MortuaryClaim;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class MortuaryController extends Controller
{
    public function index(Request $request)
    {
        $clientIdentifier = $request->query('client_identifier');
        
        if (!$clientIdentifier) {
            return response()->json(['error' => 'Client identifier is required'], 400);
        }

        try {
            $members = MortuaryMember::where('client_identifier', $clientIdentifier)
                ->with(['contributions', 'claims'])
                ->get();

            $contributions = MortuaryContribution::whereHas('member', function ($query) use ($clientIdentifier) {
                $query->where('client_identifier', $clientIdentifier);
            })->get();

            $claims = MortuaryClaim::whereHas('member', function ($query) use ($clientIdentifier) {
                $query->where('client_identifier', $clientIdentifier);
            })->get();

            return response()->json([
                'data' => [
                    'members' => $members,
                    'contributions' => $contributions,
                    'claims' => $claims
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch mortuary data'], 500);
        }
    }

    public function storeMember(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:male,female,other',
            'contact_number' => 'required|string|max:20',
            'address' => 'required|string',
            'membership_start_date' => 'required|date',
            'contribution_amount' => 'required|numeric|min:0',
            'contribution_frequency' => 'required|in:monthly,quarterly,annually',
            'status' => 'required|in:active,inactive',
            'group' => 'nullable|string|max:255',
            'client_identifier' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $member = MortuaryMember::create($request->all());
            
            return response()->json([
                'message' => 'Member created successfully',
                'data' => $member
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create member'], 500);
        }
    }

    public function showMember($id)
    {
        try {
            $member = MortuaryMember::with(['contributions', 'claims'])->findOrFail($id);
            
            return response()->json(['data' => $member]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Member not found'], 404);
        }
    }

    public function updateMember(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:male,female,other',
            'contact_number' => 'required|string|max:20',
            'address' => 'required|string',
            'membership_start_date' => 'required|date',
            'contribution_amount' => 'required|numeric|min:0',
            'contribution_frequency' => 'required|in:monthly,quarterly,annually',
            'status' => 'required|in:active,inactive',
            'group' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $member = MortuaryMember::findOrFail($id);
            $member->update($request->all());
            
            return response()->json([
                'message' => 'Member updated successfully',
                'data' => $member
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update member'], 500);
        }
    }

    public function deleteMember($id)
    {
        try {
            $member = MortuaryMember::findOrFail($id);
            $member->delete();
            
            return response()->json(['message' => 'Member deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete member'], 500);
        }
    }

    public function recordContribution(Request $request, $memberId)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string|max:100',
            'reference_number' => 'nullable|string|max:100',
            'client_identifier' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $member = MortuaryMember::where('id', $memberId)
                ->where('client_identifier', $request->client_identifier)
                ->firstOrFail();

            $contribution = MortuaryContribution::create([
                'member_id' => $memberId,
                'amount' => $request->amount,
                'payment_date' => $request->payment_date,
                'payment_method' => $request->payment_method,
                'reference_number' => $request->reference_number
            ]);
            
            return response()->json([
                'message' => 'Contribution recorded successfully',
                'data' => $contribution
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to record contribution'], 500);
        }
    }

    public function updateContribution(Request $request, $memberId, $contributionId)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string|max:100',
            'reference_number' => 'nullable|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $contribution = MortuaryContribution::where('id', $contributionId)
                ->where('member_id', $memberId)
                ->firstOrFail();

            $contribution->update($request->all());
            
            return response()->json([
                'message' => 'Contribution updated successfully',
                'data' => $contribution
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update contribution'], 500);
        }
    }

    public function getMemberContributions($memberId)
    {
        try {
            $contributions = MortuaryContribution::where('member_id', $memberId)->get();
            
            return response()->json(['data' => $contributions]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch contributions'], 500);
        }
    }

    public function deleteContribution($memberId, $contributionId)
    {
        try {
            $contribution = MortuaryContribution::where('id', $contributionId)
                ->where('member_id', $memberId)
                ->firstOrFail();

            $contribution->delete();
            
            return response()->json(['message' => 'Contribution deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete contribution'], 500);
        }
    }

    public function submitClaim(Request $request, $memberId)
    {
        $validator = Validator::make($request->all(), [
            'claim_type' => 'required|in:funeral_service,burial_plot,transportation,memorial_service,other',
            'amount' => 'required|numeric|min:0',
            'date_of_death' => 'required|date',
            'deceased_name' => 'required|string|max:255',
            'relationship_to_member' => 'required|string|max:100',
            'cause_of_death' => 'nullable|string',
            'client_identifier' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $member = MortuaryMember::where('id', $memberId)
                ->where('client_identifier', $request->client_identifier)
                ->firstOrFail();

            $claim = MortuaryClaim::create([
                'member_id' => $memberId,
                'claim_type' => $request->claim_type,
                'amount' => $request->amount,
                'date_of_death' => $request->date_of_death,
                'deceased_name' => $request->deceased_name,
                'relationship_to_member' => $request->relationship_to_member,
                'cause_of_death' => $request->cause_of_death,
                'status' => 'pending'
            ]);
            
            return response()->json([
                'message' => 'Claim submitted successfully',
                'data' => $claim
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to submit claim'], 500);
        }
    }

    public function updateClaim(Request $request, $memberId, $claimId)
    {
        $validator = Validator::make($request->all(), [
            'claim_type' => 'required|in:funeral_service,burial_plot,transportation,memorial_service,other',
            'amount' => 'required|numeric|min:0',
            'date_of_death' => 'required|date',
            'deceased_name' => 'required|string|max:255',
            'relationship_to_member' => 'required|string|max:100',
            'cause_of_death' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $claim = MortuaryClaim::where('id', $claimId)
                ->where('member_id', $memberId)
                ->firstOrFail();

            $claim->update($request->all());
            
            return response()->json([
                'message' => 'Claim updated successfully',
                'data' => $claim
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update claim'], 500);
        }
    }

    public function updateClaimStatus(Request $request, $claimId)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,approved,rejected',
            'remarks' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $claim = MortuaryClaim::findOrFail($claimId);
            $claim->update([
                'status' => $request->status,
                'remarks' => $request->remarks
            ]);
            
            return response()->json([
                'message' => 'Claim status updated successfully',
                'data' => $claim
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update claim status'], 500);
        }
    }

    public function toggleActiveStatus(Request $request, $claimId)
    {
        $validator = Validator::make($request->all(), [
            'is_active' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $claim = MortuaryClaim::findOrFail($claimId);
            $claim->update(['is_active' => $request->is_active]);
            
            return response()->json([
                'message' => 'Claim active status updated successfully',
                'data' => $claim
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update claim active status'], 500);
        }
    }

    public function getMemberClaims($memberId)
    {
        try {
            $claims = MortuaryClaim::where('member_id', $memberId)->get();
            
            return response()->json(['data' => $claims]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch claims'], 500);
        }
    }

    public function deleteClaim($memberId, $claimId)
    {
        try {
            $claim = MortuaryClaim::where('id', $claimId)
                ->where('member_id', $memberId)
                ->firstOrFail();

            $claim->delete();
            
            return response()->json(['message' => 'Claim deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete claim'], 500);
        }
    }

    public function generateReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'client_identifier' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            $clientIdentifier = $request->client_identifier;

            $report = [
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ],
                'summary' => [
                    'total_members' => MortuaryMember::where('client_identifier', $clientIdentifier)->count(),
                    'active_members' => MortuaryMember::where('client_identifier', $clientIdentifier)
                        ->where('status', 'active')->count(),
                    'total_contributions' => MortuaryContribution::whereHas('member', function ($query) use ($clientIdentifier) {
                        $query->where('client_identifier', $clientIdentifier);
                    })->whereBetween('payment_date', [$startDate, $endDate])->sum('amount'),
                    'total_claims' => MortuaryClaim::whereHas('member', function ($query) use ($clientIdentifier) {
                        $query->where('client_identifier', $clientIdentifier);
                    })->whereBetween('date_of_death', [$startDate, $endDate])->sum('amount'),
                    'pending_claims' => MortuaryClaim::whereHas('member', function ($query) use ($clientIdentifier) {
                        $query->where('client_identifier', $clientIdentifier);
                    })->where('status', 'pending')->count()
                ],
                'contributions_by_month' => MortuaryContribution::whereHas('member', function ($query) use ($clientIdentifier) {
                    $query->where('client_identifier', $clientIdentifier);
                })->whereBetween('payment_date', [$startDate, $endDate])
                    ->selectRaw('DATE_FORMAT(payment_date, "%Y-%m") as month, SUM(amount) as total')
                    ->groupBy('month')
                    ->orderBy('month')
                    ->get(),
                'claims_by_type' => MortuaryClaim::whereHas('member', function ($query) use ($clientIdentifier) {
                    $query->where('client_identifier', $clientIdentifier);
                })->whereBetween('date_of_death', [$startDate, $endDate])
                    ->selectRaw('claim_type, COUNT(*) as count, SUM(amount) as total')
                    ->groupBy('claim_type')
                    ->get()
            ];

            return response()->json(['data' => $report]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to generate report'], 500);
        }
    }
} 