<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class HealthInsuranceMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_identifier',
        'name',
        'date_of_birth',
        'gender',
        'contact_number',
        'address',
        'membership_start_date',
        'contribution_amount',
        'contribution_frequency',
        'status',
        'group'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'membership_start_date' => 'date',
        'contribution_amount' => 'decimal:2'
    ];

    public function contributions()
    {
        return $this->hasMany(HealthInsuranceContribution::class, 'member_id');
    }

    public function claims()
    {
        return $this->hasMany(HealthInsuranceClaim::class, 'member_id');
    }
} 