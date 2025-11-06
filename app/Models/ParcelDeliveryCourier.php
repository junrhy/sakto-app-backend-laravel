<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParcelDeliveryCourier extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_identifier',
        'name',
        'phone',
        'email',
        'vehicle_type',
        'status',
        'current_location',
        'current_coordinates',
        'notes',
    ];

    /**
     * Get all deliveries assigned to this courier.
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(ParcelDelivery::class, 'courier_id');
    }

    /**
     * Scope a query to only include couriers for a specific client.
     */
    public function scopeForClient($query, $clientIdentifier)
    {
        return $query->where('client_identifier', $clientIdentifier);
    }

    /**
     * Scope a query to only include available couriers.
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    /**
     * Scope a query to only include busy couriers.
     */
    public function scopeBusy($query)
    {
        return $query->where('status', 'busy');
    }

    /**
     * Scope a query to only include offline couriers.
     */
    public function scopeOffline($query)
    {
        return $query->where('status', 'offline');
    }

    /**
     * Check if courier is available.
     */
    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    /**
     * Mark courier as available.
     */
    public function markAsAvailable(): void
    {
        $this->update(['status' => 'available']);
    }

    /**
     * Mark courier as busy.
     */
    public function markAsBusy(): void
    {
        $this->update(['status' => 'busy']);
    }

    /**
     * Mark courier as offline.
     */
    public function markAsOffline(): void
    {
        $this->update(['status' => 'offline']);
    }
}

