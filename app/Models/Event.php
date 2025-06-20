<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'title',
        'description',
        'start_date',
        'end_date',
        'location', 
        'max_participants',
        'registration_deadline',
        'is_public',
        'category',
        'image',
        'status',
        'client_identifier'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'registration_deadline' => 'datetime',
        'is_public' => 'boolean',
        'max_participants' => 'integer',
    ];

    public function participants()
    {
        return $this->hasMany(EventParticipant::class);
    }
}
