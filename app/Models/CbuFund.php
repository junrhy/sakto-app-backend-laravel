<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CbuFund extends Model
{
    protected $fillable = [
        'name',
        'description',
        'target_amount',
        'total_amount',
        'value_per_share',
        'number_of_shares',
        'frequency',
        'start_date',
        'end_date',
        'status',
        'client_identifier',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    /**
     * Get the contributions for the fund.
     */
    public function contributions(): HasMany
    {
        return $this->hasMany(CbuContribution::class);
    }

    /**
     * Get the history records for the fund.
     */
    public function history(): HasMany
    {
        return $this->hasMany(CbuHistory::class);
    }

    /**
     * Get the dividends for the fund.
     */
    public function dividends(): HasMany
    {
        return $this->hasMany(CbuDividend::class);
    }
} 