<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class ClinicInventoryItem extends Model
{
    protected $fillable = [
        'name',
        'description',
        'type',
        'category',
        'sku',
        'barcode',
        'unit_price',
        'current_stock',
        'minimum_stock',
        'maximum_stock',
        'unit_of_measure',
        'expiry_date',
        'supplier',
        'location',
        'is_active',
        'client_identifier'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'expiry_date' => 'date',
        'is_active' => 'boolean',
        'current_stock' => 'integer',
        'minimum_stock' => 'integer',
        'maximum_stock' => 'integer'
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(ClinicInventoryTransaction::class);
    }

    public function scopeForClient($query, $clientIdentifier)
    {
        return $query->where('client_identifier', $clientIdentifier);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeLowStock($query)
    {
        return $query->whereRaw('current_stock <= minimum_stock');
    }

    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('expiry_date', '<=', Carbon::now()->addDays($days))
                    ->where('expiry_date', '>', Carbon::now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', Carbon::now());
    }

    public function isLowStock(): bool
    {
        return $this->current_stock <= $this->minimum_stock;
    }

    public function isExpiringSoon($days = 30): bool
    {
        if (!$this->expiry_date) {
            return false;
        }
        return $this->expiry_date <= Carbon::now()->addDays($days) && 
               $this->expiry_date > Carbon::now();
    }

    public function isExpired(): bool
    {
        if (!$this->expiry_date) {
            return false;
        }
        return $this->expiry_date < Carbon::now();
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->isExpired()) {
            return 'expired';
        }
        if ($this->isExpiringSoon()) {
            return 'expiring_soon';
        }
        if ($this->isLowStock()) {
            return 'low_stock';
        }
        return 'normal';
    }
}
