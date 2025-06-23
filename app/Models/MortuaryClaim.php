<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MortuaryClaim extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'claim_type',
        'amount',
        'date_of_death',
        'deceased_name',
        'relationship_to_member',
        'cause_of_death',
        'status',
        'remarks',
        'is_active'
    ];

    protected $casts = [
        'date_of_death' => 'date',
        'amount' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    public function member()
    {
        return $this->belongsTo(MortuaryMember::class, 'member_id');
    }
} 