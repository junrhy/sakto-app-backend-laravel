<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CbuHistory extends Model
{
    protected $fillable = [
        'cbu_fund_id',
        'action',
        'amount',
        'notes',
        'date',
        'client_identifier',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'datetime',
    ];

    /**
     * Get the fund that owns the history record.
     */
    public function fund(): BelongsTo
    {
        return $this->belongsTo(CbuFund::class, 'cbu_fund_id');
    }
} 