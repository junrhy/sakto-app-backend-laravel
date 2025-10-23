<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FnbOnlineStore extends Model
{
    protected $fillable = [
        'client_identifier',
        'name',
        'description',
        'domain',
        'is_active',
        'menu_items',
        'settings',
        'verification_required',
        'payment_negotiation_enabled',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'menu_items' => 'array',
        'settings' => 'array',
        'payment_negotiation_enabled' => 'boolean',
    ];

    /**
     * Get the menu items for this store
     */
    public function getMenuItems()
    {
        if (!$this->menu_items) {
            return collect();
        }

        return FnbMenuItem::whereIn('id', $this->menu_items)
            ->where('client_identifier', $this->client_identifier)
            ->where('is_available_online', true)
            ->get();
    }

    /**
     * Scope for active stores
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for client
     */
    public function scopeForClient($query, $clientIdentifier)
    {
        return $query->where('client_identifier', $clientIdentifier);
    }
}