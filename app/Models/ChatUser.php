<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class ChatUser extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'client_identifier',
        'username',
        'email',
        'password',
        'display_name',
        'avatar_url',
        'is_online',
        'last_seen_at',
        'preferences',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'preferences' => 'array',
        'is_online' => 'boolean',
        'is_active' => 'boolean',
        'last_seen_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Scope to filter by client identifier
     */
    public function scopeForClient($query, string $clientIdentifier)
    {
        return $query->where('client_identifier', $clientIdentifier);
    }

    /**
     * Scope to get online users
     */
    public function scopeOnline($query)
    {
        return $query->where('is_online', true);
    }

    /**
     * Scope to get active users
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Update online status
     */
    public function setOnline(bool $status = true): void
    {
        $this->update([
            'is_online' => $status,
            'last_seen_at' => $status ? now() : $this->last_seen_at,
        ]);
    }

    /**
     * Get user's chat conversations
     */
    public function chatConversations()
    {
        return $this->hasMany(ChatConversation::class, 'created_by');
    }

    /**
     * Get user's chat messages
     */
    public function chatMessages()
    {
        return $this->hasMany(ChatMessage::class, 'sender_id');
    }
}
