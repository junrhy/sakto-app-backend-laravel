<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CbuContribution extends Model
{
    protected $fillable = [
        'cbu_fund_id',
        'amount',
        'contribution_date',
        'notes',
        'contributor_name',
        'client_identifier',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'contribution_date' => 'datetime',
    ];

    /**
     * Get the fund that owns the contribution.
     */
    public function fund(): BelongsTo
    {
        return $this->belongsTo(CbuFund::class, 'cbu_fund_id');
    }
} 