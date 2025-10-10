<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChatConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_identifier',
        'title',
        'type',
        'participants',
        'created_by',
        'last_message_at',
        'is_active',
    ];

    protected $casts = [
        'participants' => 'array',
        'last_message_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user who created the conversation.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the messages for the conversation.
     */
    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'chat_conversation_id')->orderBy('created_at', 'asc');
    }

    /**
     * Get the latest message for the conversation.
     */
    public function latestMessage(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'chat_conversation_id')->latest();
    }

    /**
     * Get the participants as Contact models.
     * Note: This will need to be handled in the frontend by fetching contact details
     * from the contacts API using the participant IDs.
     */
    public function participantContacts()
    {
        // This method is kept for compatibility but actual contact fetching
        // should be done in the frontend using the contacts API
        return $this->participants;
    }

    /**
     * Check if a user is a participant in this conversation.
     */
    public function hasParticipant(int $userId): bool
    {
        return in_array($userId, $this->participants);
    }

    /**
     * Add a participant to the conversation.
     */
    public function addParticipant(int $userId): void
    {
        $participants = $this->participants;
        if (!in_array($userId, $participants)) {
            $participants[] = $userId;
            $this->update(['participants' => $participants]);
        }
    }

    /**
     * Remove a participant from the conversation.
     */
    public function removeParticipant(int $userId): void
    {
        $participants = $this->participants;
        $key = array_search($userId, $participants);
        if ($key !== false) {
            unset($participants[$key]);
            $this->update(['participants' => array_values($participants)]);
        }
    }

    /**
     * Update the last message timestamp.
     */
    public function updateLastMessage(): void
    {
        $this->update(['last_message_at' => now()]);
    }

    /**
     * Scope to filter by client identifier.
     */
    public function scopeForClient($query, string $clientIdentifier)
    {
        return $query->where('client_identifier', $clientIdentifier);
    }

    /**
     * Scope to get conversations for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->whereJsonContains('participants', $userId);
    }
}
