<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChallengeParticipant extends Model
{
    protected $fillable = [
        'challenge_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'zip_code',
        'status',
        'client_identifier'
    ];

    public function challenge()
    {
        return $this->belongsTo(Challenge::class);
    }
}
