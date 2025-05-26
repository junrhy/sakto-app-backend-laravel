<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealthInsuranceClaim extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'claim_type',
        'amount',
        'date_of_service',
        'hospital_name',
        'diagnosis',
        'status',
        'remarks'
    ];

    protected $casts = [
        'date_of_service' => 'date',
        'amount' => 'decimal:2'
    ];

    public function member()
    {
        return $this->belongsTo(HealthInsuranceMember::class, 'member_id');
    }
} 