<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParcelDeliveryExternalIntegration extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_identifier',
        'service_name',
        'api_key',
        'api_secret',
        'is_active',
        'settings',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * Scope a query to only include integrations for a specific client.
     */
    public function scopeForClient($query, $clientIdentifier)
    {
        return $query->where('client_identifier', $clientIdentifier);
    }

    /**
     * Scope a query to only include active integrations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include integrations for a specific service.
     */
    public function scopeForService($query, $serviceName)
    {
        return $query->where('service_name', $serviceName);
    }

    /**
     * Get active integration for a service and client.
     */
    public static function getActiveIntegration($clientIdentifier, $serviceName)
    {
        return static::active()
            ->forClient($clientIdentifier)
            ->forService($serviceName)
            ->first();
    }
}

