<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatConversation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Broadcast;

class ChatMessageController extends Controller
{
    /**
     * Get messages for a conversation.
     */
    public function index(Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        $conversationId = $request->input('conversation_id');
        $userId = $request->input('user_id');

        if (!$clientIdentifier || !$conversationId || !$userId) {
            return response()->json(['error' => 'client_identifier, conversation_id, and user_id are required'], 400);
        }

        // Check if user has access to this conversation
        $conversation = ChatConversation::forClient($clientIdentifier)
            ->forUser($userId)
            ->find($conversationId);

        if (!$conversation) {
            return response()->json([
                'status' => 'error',
                'message' => 'Conversation not found or access denied'
            ], 404);
        }

        $messages = ChatMessage::forClient($clientIdentifier)
            ->where('chat_conversation_id', $conversationId)
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $messages
        ]);
    }

    /**
     * Send a new message.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_identifier' => 'required|string',
            'chat_conversation_id' => 'required|integer|exists:chat_conversations,id',
            'sender_id' => 'required|integer|exists:users,id',
            'content' => 'required|string',
            'message_type' => 'nullable|string|in:text,image,file',
            'metadata' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $clientIdentifier = $request->input('client_identifier');
        $conversationId = $request->input('chat_conversation_id');
        $senderId = $request->input('sender_id');

        // Check if sender has access to this conversation
        $conversation = ChatConversation::forClient($clientIdentifier)
            ->forUser($senderId)
            ->find($conversationId);

        if (!$conversation) {
            return response()->json([
                'status' => 'error',
                'message' => 'Conversation not found or access denied'
            ], 404);
        }

        $message = ChatMessage::create($request->all());

        // Update conversation's last message timestamp
        $conversation->updateLastMessage();

        // Broadcast the message to other participants
        $this->broadcastMessage($message);

        return response()->json([
            'status' => 'success',
            'data' => $message->load('sender')
        ], 201);
    }

    /**
     * Get a specific message.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        $userId = $request->input('user_id');

        if (!$clientIdentifier || !$userId) {
            return response()->json(['error' => 'client_identifier and user_id are required'], 400);
        }

        $message = ChatMessage::forClient($clientIdentifier)
            ->with(['sender', 'chatConversation'])
            ->find($id);

        if (!$message) {
            return response()->json([
                'status' => 'error',
                'message' => 'Message not found'
            ], 404);
        }

        // Check if user has access to this conversation
        if (!$message->chatConversation->hasParticipant($userId)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied'
            ], 403);
        }

        return response()->json([
            'status' => 'success',
            'data' => $message
        ]);
    }

    /**
     * Update a message.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        $userId = $request->input('user_id');

        if (!$clientIdentifier || !$userId) {
            return response()->json(['error' => 'client_identifier and user_id are required'], 400);
        }

        $message = ChatMessage::forClient($clientIdentifier)
            ->with('chatConversation')
            ->find($id);

        if (!$message) {
            return response()->json([
                'status' => 'error',
                'message' => 'Message not found'
            ], 404);
        }

        // Check if user is the sender
        if ($message->sender_id != $userId) {
            return response()->json([
                'status' => 'error',
                'message' => 'You can only edit your own messages'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
            'message_type' => 'nullable|string|in:text,image,file',
            'metadata' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $message->update($request->only(['content', 'message_type', 'metadata']));

        return response()->json([
            'status' => 'success',
            'data' => $message->load('sender')
        ]);
    }

    /**
     * Delete a message.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        $userId = $request->input('user_id');

        if (!$clientIdentifier || !$userId) {
            return response()->json(['error' => 'client_identifier and user_id are required'], 400);
        }

        $message = ChatMessage::forClient($clientIdentifier)->find($id);

        if (!$message) {
            return response()->json([
                'status' => 'error',
                'message' => 'Message not found'
            ], 404);
        }

        // Check if user is the sender
        if ($message->sender_id != $userId) {
            return response()->json([
                'status' => 'error',
                'message' => 'You can only delete your own messages'
            ], 403);
        }

        $message->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Message deleted successfully'
        ]);
    }

    /**
     * Mark messages as read.
     */
    public function markAsRead(Request $request): JsonResponse
    {
        $clientIdentifier = $request->input('client_identifier');
        $conversationId = $request->input('conversation_id');
        $userId = $request->input('user_id');

        if (!$clientIdentifier || !$conversationId || !$userId) {
            return response()->json(['error' => 'client_identifier, conversation_id, and user_id are required'], 400);
        }

        // Check if user has access to this conversation
        $conversation = ChatConversation::forClient($clientIdentifier)
            ->forUser($userId)
            ->find($conversationId);

        if (!$conversation) {
            return response()->json([
                'status' => 'error',
                'message' => 'Conversation not found or access denied'
            ], 404);
        }

        // Mark all unread messages in this conversation as read
        ChatMessage::forClient($clientIdentifier)
            ->where('chat_conversation_id', $conversationId)
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Messages marked as read'
        ]);
    }

    /**
     * Broadcast message to conversation participants.
     */
    private function broadcastMessage(ChatMessage $message): void
    {
        $conversation = $message->chatConversation;
        
        // Broadcast to all participants except the sender
        foreach ($conversation->participants as $participantId) {
            if ($participantId != $message->sender_id) {
                Broadcast::channel("chat-conversation-{$conversation->id}-user-{$participantId}")
                    ->send('new-message', [
                        'message' => $message->load('sender'),
                        'conversation' => $conversation
                    ]);
            }
        }
    }
}
