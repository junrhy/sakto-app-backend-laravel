<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicInventoryTransaction extends Model
{
    protected $fillable = [
        'clinic_inventory_item_id',
        'transaction_type',
        'quantity',
        'unit_price',
        'total_amount',
        'notes',
        'reference_number',
        'performed_by',
        'transaction_date',
        'client_identifier'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'quantity' => 'integer',
        'transaction_date' => 'date'
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(ClinicInventoryItem::class);
    }

    public function scopeForClient($query, $clientIdentifier)
    {
        return $query->where('client_identifier', $clientIdentifier);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('transaction_type', $type);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    public function scopeInbound($query)
    {
        return $query->where('transaction_type', 'in');
    }

    public function scopeOutbound($query)
    {
        return $query->where('transaction_type', 'out');
    }
}
