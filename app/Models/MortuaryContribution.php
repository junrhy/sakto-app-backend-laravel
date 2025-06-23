<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MortuaryContribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference_number'
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2'
    ];

    public function member()
    {
        return $this->belongsTo(MortuaryMember::class, 'member_id');
    }
} 