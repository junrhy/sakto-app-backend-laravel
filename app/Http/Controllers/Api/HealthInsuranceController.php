<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\HealthInsuranceMember;
use App\Models\HealthInsuranceContribution;
use App\Models\HealthInsuranceClaim;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HealthInsuranceController extends Controller
{
    /**
     * Get all health insurance data for the authenticated client
     */
    public function index(Request $request)
    {
        try {
            $clientIdentifier = $request->client_identifier;
            
            $members = HealthInsuranceMember::where('client_identifier', $clientIdentifier)
                ->with(['contributions', 'claims'])
                ->get();

            $data = [
                'members' => $members,
                'contributions' => HealthInsuranceContribution::whereIn('member_id', $members->pluck('id'))->get(),
                'claims' => HealthInsuranceClaim::whereIn('member_id', $members->pluck('id'))->get(),
            ];

            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            Log::error('Error fetching health insurance data: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch health insurance data'], 500);
        }
    }

    /**
     * Store a new health insurance member
     */
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
            'status' => 'required|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $member = HealthInsuranceMember::create([
                'client_identifier' => $request->client_identifier,
                'name' => $request->name,
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'contact_number' => $request->contact_number,
                'address' => $request->address,
                'membership_start_date' => $request->membership_start_date,
                'contribution_amount' => $request->contribution_amount,
                'contribution_frequency' => $request->contribution_frequency,
                'status' => $request->status
            ]);

            return response()->json(['data' => $member], 201);
        } catch (\Exception $e) {
            Log::error('Error storing member: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to store member'], 500);
        }
    }

    /**
     * Update an existing health insurance member
     */
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
            'status' => 'required|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $member = HealthInsuranceMember::where('id', $id)
                ->where('client_identifier', $request->client_identifier)
                ->firstOrFail();

            $member->update($request->all());

            return response()->json(['data' => $member]);
        } catch (\Exception $e) {
            Log::error('Error updating member: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update member'], 500);
        }
    }

    /**
     * Record a new contribution for a member
     */
    public function recordContribution(Request $request, $memberId)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'reference_number' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $member = HealthInsuranceMember::where('id', $memberId)
                ->where('client_identifier', $request->client_identifier)
                ->firstOrFail();

            $contribution = HealthInsuranceContribution::create([
                'member_id' => $memberId,
                'amount' => $request->amount,
                'payment_date' => $request->payment_date,
                'payment_method' => $request->payment_method,
                'reference_number' => $request->reference_number
            ]);

            return response()->json(['data' => $contribution], 201);
        } catch (\Exception $e) {
            Log::error('Error recording contribution: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to record contribution'], 500);
        }
    }

    /**
     * Get all contributions for a member
     */
    public function getMemberContributions($memberId)
    {
        try {
            $member = HealthInsuranceMember::where('id', $memberId)
                ->where('client_identifier', request()->client_identifier)
                ->firstOrFail();

            $contributions = HealthInsuranceContribution::where('member_id', $memberId)
                ->orderBy('payment_date', 'desc')
                ->get();

            return response()->json(['data' => $contributions]);
        } catch (\Exception $e) {
            Log::error('Error fetching member contributions: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch member contributions'], 500);
        }
    }

    /**
     * Update an existing contribution for a member
     */
    public function updateContribution(Request $request, $memberId, $contributionId)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'reference_number' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $member = HealthInsuranceMember::where('id', $memberId)
                ->where('client_identifier', $request->client_identifier)
                ->firstOrFail();

            $contribution = HealthInsuranceContribution::where('id', $contributionId)
                ->where('member_id', $memberId)
                ->firstOrFail();

            $contribution->update([
                'amount' => $request->amount,
                'payment_date' => $request->payment_date,
                'payment_method' => $request->payment_method,
                'reference_number' => $request->reference_number
            ]);

            return response()->json(['data' => $contribution]);
        } catch (\Exception $e) {
            Log::error('Error updating contribution: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update contribution'], 500);
        }
    }

    /**
     * Submit a new claim for a member
     */
    public function submitClaim(Request $request, $memberId)
    {
        $validator = Validator::make($request->all(), [
            'claim_type' => 'required|in:hospitalization,outpatient,dental,optical,prescription',
            'amount' => 'required|numeric|min:0',
            'date_of_service' => 'required|date',
            'hospital_name' => 'required|string',
            'diagnosis' => 'required|string',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $member = HealthInsuranceMember::where('id', $memberId)
                ->where('client_identifier', $request->client_identifier)
                ->firstOrFail();

            $claim = HealthInsuranceClaim::create([
                'member_id' => $memberId,
                'claim_type' => $request->claim_type,
                'amount' => $request->amount,
                'date_of_service' => $request->date_of_service,
                'hospital_name' => $request->hospital_name,
                'diagnosis' => $request->diagnosis,
                'status' => 'pending',
                'is_active' => $request->input('is_active', true)
            ]);

            return response()->json(['data' => $claim], 201);
        } catch (\Exception $e) {
            Log::error('Error submitting claim: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to submit claim'], 500);
        }
    }

    /**
     * Update the status of a claim
     */
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
            $claim = HealthInsuranceClaim::whereHas('member', function ($query) {
                $query->where('client_identifier', request()->client_identifier);
            })->findOrFail($claimId);

            $claim->update([
                'status' => $request->status,
                'remarks' => $request->remarks
            ]);

            return response()->json(['data' => $claim]);
        } catch (\Exception $e) {
            Log::error('Error updating claim status: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update claim status'], 500);
        }
    }

    /**
     * Get all claims for a member
     */
    public function getMemberClaims($memberId)
    {
        try {
            $member = HealthInsuranceMember::where('id', $memberId)
                ->where('client_identifier', request()->client_identifier)
                ->firstOrFail();

            $claims = HealthInsuranceClaim::where('member_id', $memberId)
                ->orderBy('date_of_service', 'desc')
                ->get();

            return response()->json(['data' => $claims]);
        } catch (\Exception $e) {
            Log::error('Error fetching member claims: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch member claims'], 500);
        }
    }

    /**
     * Toggle the active status of a claim
     */
    public function toggleActiveStatus(Request $request, $claimId)
    {
        $validator = Validator::make($request->all(), [
            'is_active' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $claim = HealthInsuranceClaim::whereHas('member', function ($query) {
                $query->where('client_identifier', request()->client_identifier);
            })->findOrFail($claimId);

            $claim->update([
                'is_active' => $request->is_active
            ]);

            return response()->json(['data' => $claim]);
        } catch (\Exception $e) {
            Log::error('Error toggling claim active status: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to toggle claim active status'], 500);
        }
    }

    /**
     * Generate health insurance reports
     */
    public function generateReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'report_type' => 'required|in:contributions,claims,members'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $clientIdentifier = $request->client_identifier;
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            $reportType = $request->report_type;

            $report = match($reportType) {
                'contributions' => $this->generateContributionsReport($clientIdentifier, $startDate, $endDate),
                'claims' => $this->generateClaimsReport($clientIdentifier, $startDate, $endDate),
                'members' => $this->generateMembersReport($clientIdentifier, $startDate, $endDate),
                default => throw new \Exception('Invalid report type')
            };

            return response()->json(['data' => $report]);
        } catch (\Exception $e) {
            Log::error('Error generating report: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to generate report'], 500);
        }
    }

    /**
     * Generate contributions report
     */
    private function generateContributionsReport($clientIdentifier, $startDate, $endDate)
    {
        return HealthInsuranceContribution::whereHas('member', function ($query) use ($clientIdentifier) {
            $query->where('client_identifier', $clientIdentifier);
        })
        ->whereBetween('payment_date', [$startDate, $endDate])
        ->with('member')
        ->get()
        ->groupBy('payment_method')
        ->map(function ($contributions) {
            return [
                'total_amount' => $contributions->sum('amount'),
                'count' => $contributions->count(),
                'contributions' => $contributions
            ];
        });
    }

    /**
     * Generate claims report
     */
    private function generateClaimsReport($clientIdentifier, $startDate, $endDate)
    {
        return HealthInsuranceClaim::whereHas('member', function ($query) use ($clientIdentifier) {
            $query->where('client_identifier', $clientIdentifier);
        })
        ->whereBetween('date_of_service', [$startDate, $endDate])
        ->with('member')
        ->get()
        ->groupBy('status')
        ->map(function ($claims) {
            return [
                'total_amount' => $claims->sum('amount'),
                'count' => $claims->count(),
                'claims' => $claims
            ];
        });
    }

    /**
     * Generate members report
     */
    private function generateMembersReport($clientIdentifier, $startDate, $endDate)
    {
        return HealthInsuranceMember::where('client_identifier', $clientIdentifier)
            ->whereBetween('membership_start_date', [$startDate, $endDate])
            ->with(['contributions', 'claims'])
            ->get()
            ->map(function ($member) {
                return [
                    'member' => $member,
                    'total_contributions' => $member->contributions->sum('amount'),
                    'total_claims' => $member->claims->sum('amount'),
                    'active_claims' => $member->claims->where('status', 'pending')->count()
                ];
            });
    }

    /**
     * Delete a health insurance member
     */
    public function deleteMember(string $id)
    {
        try {
            $member = HealthInsuranceMember::where('id', $id)
                ->first();

            if (!$member) {
                return response()->json(['message' => 'Member not found'], 404);
            }

            // Delete associated contributions and claims
            HealthInsuranceContribution::where('member_id', $member->id)->delete();
            HealthInsuranceClaim::where('member_id', $member->id)->delete();
            
            $member->delete();

            return response()->json(['message' => 'Member deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Error deleting member: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete member'], 500);
        }
    }

    public function deleteContribution(string $memberId, string $contributionId)
    {
        try {
            $contribution = HealthInsuranceContribution::where('id', $contributionId)
                ->where('member_id', $memberId)
                ->first();

            if (!$contribution) {
                return response()->json(['message' => 'Contribution not found'], 404);
            }

            $contribution->delete();

            return response()->json(['message' => 'Contribution deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Error deleting contribution: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete contribution'], 500);
        }
    }

    public function deleteClaim(string $memberId, string $claimId)
    {
        try {
            $claim = HealthInsuranceClaim::where('id', $claimId)
                ->where('member_id', $memberId)
                ->first();

            if (!$claim) {
                return response()->json(['message' => 'Claim not found'], 404);
            }

            $claim->delete();

            return response()->json(['message' => 'Claim deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Error deleting claim: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete claim'], 500);
        }
    }

    public function updateClaim(Request $request, string $memberId, string $claimId)
    {
        $validated = $request->validate([
            'claim_type' => 'required|in:hospitalization,outpatient,dental,optical',
            'amount' => 'required|numeric|min:0',
            'date_of_service' => 'required|date',
            'hospital_name' => 'required|string',
            'diagnosis' => 'required|string',
            'status' => 'required|in:pending,approved,rejected',
            'client_identifier' => 'required|string'
        ]);

        try {
            $claim = HealthInsuranceClaim::where('id', $claimId)
                ->where('member_id', $memberId)
                ->firstOrFail();

            $claim->update([
                'claim_type' => $validated['claim_type'],
                'amount' => $validated['amount'],
                'date_of_service' => $validated['date_of_service'],
                'hospital_name' => $validated['hospital_name'],
                'diagnosis' => $validated['diagnosis'],
                'status' => $validated['status']
            ]);

            return response()->json([
                'message' => 'Claim updated successfully',
                'data' => $claim
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating claim: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update claim.'], 500);
        }
    }

    /**
     * Get member details with contributions and claims
     */
    public function showMember(Request $request, $id)
    {
        try {
            $member = HealthInsuranceMember::where('id', $id)
                ->where('client_identifier', $request->client_identifier)
                ->with(['contributions', 'claims'])
                ->firstOrFail();

            // Calculate upcoming contributions
            $upcomingContributions = $this->calculateUpcomingContributions($member);
            
            // Calculate past due contributions
            $pastDueContributions = $this->calculatePastDueContributions($member);

            return response()->json([
                'data' => [
                    'member' => $member,
                    'contributions' => $member->contributions,
                    'claims' => $member->claims,
                    'upcoming_contributions' => $upcomingContributions,
                    'past_due_contributions' => $pastDueContributions
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching member details: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch member details'], 500);
        }
    }

    /**
     * Calculate upcoming contributions for a member
     */
    private function calculateUpcomingContributions($member)
    {
        $upcoming = [];
        $now = new \DateTime();
        $frequency = $member->contribution_frequency;
        $amount = $member->contribution_amount;
        
        // Calculate next 12 contributions starting from current date
        for ($i = 0; $i < 12; $i++) {
            $dueDate = clone $now;
            
            switch ($frequency) {
                case 'monthly':
                    $dueDate->modify("+{$i} month");
                    break;
                case 'quarterly':
                    $dueDate->modify("+" . ($i * 3) . " month");
                    break;
                case 'annually':
                    $dueDate->modify("+{$i} year");
                    break;
            }
            
            $upcoming[] = [
                'due_date' => $dueDate->format('Y-m-d'),
                'amount' => $amount
            ];
        }
        
        return $upcoming;
    }

    /**
     * Calculate past due contributions for a member
     */
    private function calculatePastDueContributions($member)
    {
        $pastDue = [];
        $startDate = new \DateTime($member->membership_start_date);
        $now = new \DateTime();
        $frequency = $member->contribution_frequency;
        $amount = $member->contribution_amount;
        
        // Get all paid contribution dates
        $paidDates = $member->contributions->pluck('payment_date')->toArray();
        
        // Calculate past due contributions
        $currentDate = clone $startDate;
        while ($currentDate < $now) {
            $dueDate = clone $currentDate;
            $dueDateStr = $dueDate->format('Y-m-d');
            
            // Check if this contribution was paid
            $isPaid = false;
            foreach ($paidDates as $paidDate) {
                if (date('Y-m', strtotime($paidDate)) === date('Y-m', strtotime($dueDateStr))) {
                    $isPaid = true;
                    break;
                }
            }
            
            if (!$isPaid) {
                $pastDue[] = [
                    'due_date' => $dueDateStr,
                    'amount' => $amount,
                ];
            }
            
            // Move to next due date
            switch ($frequency) {
                case 'monthly':
                    $currentDate->modify('+1 month');
                    break;
                case 'quarterly':
                    $currentDate->modify('+3 month');
                    break;
                case 'annually':
                    $currentDate->modify('+1 year');
                    break;
            }
        }
        
        return $pastDue;
    }
}
