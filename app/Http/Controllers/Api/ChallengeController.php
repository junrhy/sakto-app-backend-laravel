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
        $challenge = Challenge::findOrFail($id);
        $participant = ChallengeParticipant::where('challenge_id', $id)
            ->where('id', $participantId)
            ->firstOrFail();
        
        $participant->delete();
        return response()->json(null, 204);
    }

    /**
     * Start timer for a participant
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function startTimer(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'participant_id' => 'required|integer|exists:challenge_participants,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $challenge = Challenge::findOrFail($id);
        $participant = ChallengeParticipant::where('challenge_id', $id)
            ->where('id', $request->participant_id)
            ->firstOrFail();
        
        $participant->startTimer();
        
        return response()->json([
            'participant' => $participant->fresh(),
            'message' => 'Timer started successfully'
        ]);
    }

    /**
     * Stop timer for a participant
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function stopTimer(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'participant_id' => 'required|integer|exists:challenge_participants,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $challenge = Challenge::findOrFail($id);
        $participant = ChallengeParticipant::where('challenge_id', $id)
            ->where('id', $request->participant_id)
            ->firstOrFail();
        
        // Debug logging
        \Log::info('StopTimer API called', [
            'participant_id' => $participant->id,
            'timer_is_active' => $participant->timer_is_active,
            'timer_started_at' => $participant->timer_started_at,
            'elapsed_time_seconds' => $participant->elapsed_time_seconds,
        ]);
        
        $participant->stopTimer();
        
        // Debug logging after stop
        $freshParticipant = $participant->fresh();
        \Log::info('StopTimer API completed', [
            'participant_id' => $freshParticipant->id,
            'timer_is_active' => $freshParticipant->timer_is_active,
            'timer_started_at' => $freshParticipant->timer_started_at,
            'timer_ended_at' => $freshParticipant->timer_ended_at,
            'elapsed_time_seconds' => $freshParticipant->elapsed_time_seconds,
        ]);
        
        return response()->json([
            'participant' => $freshParticipant,
            'message' => 'Timer stopped successfully'
        ]);
    }

    /**
     * Pause timer for a participant
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function pauseTimer(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'participant_id' => 'required|integer|exists:challenge_participants,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $challenge = Challenge::findOrFail($id);
        $participant = ChallengeParticipant::where('challenge_id', $id)
            ->where('id', $request->participant_id)
            ->firstOrFail();
        
        // Debug logging
        \Log::info('PauseTimer API called', [
            'participant_id' => $participant->id,
            'timer_is_active' => $participant->timer_is_active,
            'timer_started_at' => $participant->timer_started_at,
            'elapsed_time_seconds' => $participant->elapsed_time_seconds,
        ]);
        
        $participant->pauseTimer();
        
        // Debug logging after pause
        $freshParticipant = $participant->fresh();
        \Log::info('PauseTimer API completed', [
            'participant_id' => $freshParticipant->id,
            'timer_is_active' => $freshParticipant->timer_is_active,
            'timer_started_at' => $freshParticipant->timer_started_at,
            'timer_ended_at' => $freshParticipant->timer_ended_at,
            'elapsed_time_seconds' => $freshParticipant->elapsed_time_seconds,
        ]);
        
        return response()->json([
            'participant' => $freshParticipant,
            'message' => 'Timer paused successfully'
        ]);
    }

    /**
     * Resume timer for a participant
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function resumeTimer(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'participant_id' => 'required|integer|exists:challenge_participants,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $challenge = Challenge::findOrFail($id);
        $participant = ChallengeParticipant::where('challenge_id', $id)
            ->where('id', $request->participant_id)
            ->firstOrFail();
        
        $participant->resumeTimer();
        
        return response()->json([
            'participant' => $participant->fresh(),
            'message' => 'Timer resumed successfully'
        ]);
    }

    /**
     * Reset timer for a participant
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetTimer(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'participant_id' => 'required|integer|exists:challenge_participants,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $challenge = Challenge::findOrFail($id);
        $participant = ChallengeParticipant::where('challenge_id', $id)
            ->where('id', $request->participant_id)
            ->firstOrFail();
        
        $participant->resetTimer();
        
        return response()->json([
            'participant' => $participant->fresh(),
            'message' => 'Timer reset successfully'
        ]);
    }

    /**
     * Get timer status for a participant
     *
     * @param  int  $id
     * @param  int  $participantId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTimerStatus($id, $participantId)
    {
        $challenge = Challenge::findOrFail($id);
        $participant = ChallengeParticipant::where('challenge_id', $id)
            ->where('id', $participantId)
            ->firstOrFail();
        
        // Debug information
        \Log::info('Timer Status Debug', [
            'participant_id' => $participant->id,
            'timer_started_at' => $participant->timer_started_at,
            'timer_ended_at' => $participant->timer_ended_at,
            'timer_is_active' => $participant->timer_is_active,
            'elapsed_time_seconds_db' => $participant->elapsed_time_seconds,
            'calculated_elapsed' => $participant->getCurrentElapsedTime(),
        ]);
        
        return response()->json([
            'participant_id' => $participant->id,
            'is_running' => $participant->isTimerRunning(),
            'has_started' => $participant->hasStartedTimer(),
            'elapsed_time_seconds' => $participant->getCurrentElapsedTime(),
            'formatted_time' => $participant->getFormattedElapsedTime(),
            'timer_started_at' => $participant->timer_started_at,
            'timer_ended_at' => $participant->timer_ended_at,
        ]);
    }
}
