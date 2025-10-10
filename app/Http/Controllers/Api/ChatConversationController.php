<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ChatConversationController extends Controller
{
    /**
     * Get all conversations for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        $userId = $request->input('user_id');

        if (!$clientIdentifier || !$userId) {
            return response()->json(['error' => 'client_identifier and user_id are required'], 400);
        }

        $conversations = ChatConversation::forClient($clientIdentifier)
            ->forUser($userId)
            ->with(['latestMessage.sender', 'participantUsers'])
            ->orderBy('last_message_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $conversations
        ]);
    }

    /**
     * Create a new conversation.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string',
            'title' => 'nullable|string|max:255',
            'type' => 'required|in:direct,group',
            'participants' => 'required|array|min:1',
            'participants.*' => 'integer', // Will be contact IDs, not user IDs
            'created_by' => 'required|integer' // Will be user ID of the creator
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $conversation = ChatConversation::create($request->all());

        return response()->json([
            'status' => 'success',
            'data' => $conversation->load(['creator'])
        ], 201);
    }

    /**
     * Get a specific conversation.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        $userId = $request->input('user_id');

        if (!$clientIdentifier || !$userId) {
            return response()->json(['error' => 'client_identifier and user_id are required'], 400);
        }

        $conversation = ChatConversation::forClient($clientIdentifier)
            ->forUser($userId)
            ->with(['chatMessages.sender', 'participantUsers', 'creator'])
            ->find($id);

        if (!$conversation) {
            return response()->json([
                'status' => 'error',
                'message' => 'Conversation not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $conversation
        ]);
    }

    /**
     * Update a conversation.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        $userId = $request->input('user_id');

        if (!$clientIdentifier || !$userId) {
            return response()->json(['error' => 'client_identifier and user_id are required'], 400);
        }

        $conversation = ChatConversation::forClient($clientIdentifier)
            ->forUser($userId)
            ->find($id);

        if (!$conversation) {
            return response()->json([
                'status' => 'error',
                'message' => 'Conversation not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'type' => 'in:direct,group',
            'participants' => 'array',
            'participants.*' => 'integer|exists:users,id',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $conversation->update($request->only(['title', 'type', 'participants', 'is_active']));

        return response()->json([
            'status' => 'success',
            'data' => $conversation->load(['participantUsers', 'creator'])
        ]);
    }

    /**
     * Delete a conversation.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        $userId = $request->input('user_id');

        if (!$clientIdentifier || !$userId) {
            return response()->json(['error' => 'client_identifier and user_id are required'], 400);
        }

        $conversation = ChatConversation::forClient($clientIdentifier)
            ->forUser($userId)
            ->find($id);

        if (!$conversation) {
            return response()->json([
                'status' => 'error',
                'message' => 'Conversation not found'
            ], 404);
        }

        $conversation->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Conversation deleted successfully'
        ]);
    }

    /**
     * Add a participant to a conversation.
     */
    public function addParticipant(Request $request, int $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        $userId = $request->input('user_id');
        $participantId = $request->input('participant_id');

        if (!$clientIdentifier || !$userId || !$participantId) {
            return response()->json(['error' => 'client_identifier, user_id, and participant_id are required'], 400);
        }

        $conversation = ChatConversation::forClient($clientIdentifier)
            ->forUser($userId)
            ->find($id);

        if (!$conversation) {
            return response()->json([
                'status' => 'error',
                'message' => 'Conversation not found'
            ], 404);
        }

        $conversation->addParticipant($participantId);

        return response()->json([
            'status' => 'success',
            'data' => $conversation->load(['participantUsers', 'creator'])
        ]);
    }

    /**
     * Remove a participant from a conversation.
     */
    public function removeParticipant(Request $request, int $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        $userId = $request->input('user_id');
        $participantId = $request->input('participant_id');

        if (!$clientIdentifier || !$userId || !$participantId) {
            return response()->json(['error' => 'client_identifier, user_id, and participant_id are required'], 400);
        }

        $conversation = ChatConversation::forClient($clientIdentifier)
            ->forUser($userId)
            ->find($id);

        if (!$conversation) {
            return response()->json([
                'status' => 'error',
                'message' => 'Conversation not found'
            ], 404);
        }

        $conversation->removeParticipant($participantId);

        return response()->json([
            'status' => 'success',
            'data' => $conversation->load(['participantUsers', 'creator'])
        ]);
    }
}
