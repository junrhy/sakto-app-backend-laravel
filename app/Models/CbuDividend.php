<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CbuDividend extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'cbu_fund_id',
        'amount',
        'dividend_date',
        'notes',
        'client_identifier',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'dividend_date' => 'date',
    ];

    /**
     * Get the CBU fund that owns the dividend.
     */
    public function fund(): BelongsTo
    {
        return $this->belongsTo(CbuFund::class, 'cbu_fund_id');
    }
} 