<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Challenge;
use App\Models\ChallengeParticipant;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ChallengeController extends Controller
{
    /**
     * Display a listing of the challenges.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $challenges = Challenge::all();
        return response()->json($challenges);
    }

    /**
     * Store a newly created challenge in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'goal_type' => 'required|string',
                'goal_value' => 'required|integer',
                'goal_unit' => 'required|string',
                'visibility' => 'required|string',
                'rewards' => 'nullable|array',
                'rewards.*.type' => 'required|in:badge,points,achievement',
                'rewards.*.value' => 'required|string',
                'status' => 'required|in:active,inactive,completed',
                'client_identifier' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $challenge = Challenge::create($request->all());
            return response()->json($challenge, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified challenge.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $challenge = Challenge::findOrFail($id);
        return response()->json($challenge);
    }

    /**
     * Update the specified challenge in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after:start_date',
            'goal_type' => 'sometimes|required|string',
            'goal_value' => 'sometimes|required|integer',
            'goal_unit' => 'sometimes|required|string',
            'visibility' => 'sometimes|required|string',
            'rewards' => 'sometimes|nullable|array',
            'rewards.*.type' => 'sometimes|required|in:badge,points,achievement',
            'rewards.*.value' => 'sometimes|required|string',
            'status' => 'sometimes|required|in:active,inactive,completed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $challenge = Challenge::findOrFail($id);
        $challenge->update($request->all());
        return response()->json($challenge);
    }

    /**
     * Remove the specified challenge from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $challenge = Challenge::findOrFail($id);
        $challenge->delete();
        return response()->json(null, 204);
    }

    /**
     * Bulk delete challenges.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkDestroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'required|integer|exists:challenges,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        Challenge::whereIn('id', $request->ids)->delete();
        return response()->json(null, 204);
    }

    /**
     * Get participants for a specific challenge.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getParticipants($id)
    {
        $challenge = Challenge::findOrFail($id);
        $participants = ChallengeParticipant::where('challenge_id', $id)->get();
        return response()->json($participants);
    }

    /**
     * Update progress for a participant in a challenge.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProgress(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'participant_id' => 'required|integer|exists:challenge_participants,id',
            'progress' => 'required|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $challenge = Challenge::findOrFail($id);
        $participant = ChallengeParticipant::where('challenge_id', $id)
            ->where('id', $request->participant_id)
            ->firstOrFail();
        
        $participant->progress = $request->progress;
        $participant->save();

        return response()->json($participant);
    }

    /**
     * Update participation status for a participant in a challenge.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateParticipationStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'participant_id' => 'required|integer|exists:challenge_participants,id',
            'status' => 'required|in:joined,left,completed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $challenge = Challenge::findOrFail($id);
        
        $participant = ChallengeParticipant::findOrFail($request->participant_id);
        $participant->status = $request->status;
        $participant->save();

        return response()->json($participant);
    }

    /**
     * Get leaderboard for a specific challenge.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLeaderboard($id)
    {
        $challenge = Challenge::findOrFail($id);
        
        $leaderboard = ChallengeParticipant::where('challenge_id', $id)
            ->orderBy('progress', 'desc')
            ->get();
            
        return response()->json($leaderboard);
    }

    /**
     * Get statistics for a specific challenge.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatistics($id)
    {
        $challenge = Challenge::findOrFail($id);
        
        $statistics = [
            'total_participants' => ChallengeParticipant::where('challenge_id', $id)->count(),
            'completed_participants' => ChallengeParticipant::where('challenge_id', $id)
                ->where('status', 'completed')
                ->count(),
            'average_progress' => ChallengeParticipant::where('challenge_id', $id)
                ->avg('progress'),
            'status_distribution' => ChallengeParticipant::where('challenge_id', $id)
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get(),
        ];
        
        return response()->json($statistics);
    }

    /**
     * Get list of all participants across all challenges.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getParticipantsList()
    {
        $participants = ChallengeParticipant::with(['challenge:id,title'])->get();
        
        // Transform the data to match the expected format in the frontend
        $formattedParticipants = $participants->map(function ($participant) {
            return [
                'id' => $participant->id,
                'name' => $participant->first_name . ' ' . $participant->last_name,
                'email' => $participant->email,
                'identifier' => $participant->client_identifier
            ];
        });
        
        return response()->json($formattedParticipants);
    }

    /**
     * Add a participant to a challenge.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function addParticipant(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'client_identifier' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $challenge = Challenge::findOrFail($id);
        $request->merge(['challenge_id' => $id]);
        $participant = ChallengeParticipant::create($request->all());
        return response()->json($participant, 201);
    }

    /**
     * Remove a participant from a challenge.   
     *
     * @param  int  $id
     * @param  int  $participantId
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeParticipant($id, $participantId)
    {
        $participant = ChallengeParticipant::findOrFail($participantId);
        $participant->delete();
        return response()->json(null, 204);
    }
}
