<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Challenge extends Model
{
    protected $fillable = [
        'title',
        'description',
        'start_date',
        'end_date',
        'goal_type',
        'goal_value',
        'goal_unit',
        'visibility',
        'rewards',
        'status',
        'client_identifier'
    ];

    protected $casts = [
        'rewards' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function participants()
    {
        return $this->hasMany(ChallengeParticipant::class);
    }
}
