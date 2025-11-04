<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetailStockTransaction extends Model
{
    protected $fillable = [
        'client_identifier',
        'retail_item_id',
        'transaction_type',
        'quantity',
        'previous_quantity',
        'new_quantity',
        'reason',
        'reference_number',
        'performed_by',
        'transaction_date',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
    ];

    /**
     * Get the retail item that owns the transaction.
     */
    public function retailItem(): BelongsTo
    {
        return $this->belongsTo(RetailItem::class);
    }

    /**
     * Scope a query to only include transactions for a specific client.
     */
    public function scopeForClient($query, string $clientIdentifier)
    {
        return $query->where('client_identifier', $clientIdentifier);
    }
}
