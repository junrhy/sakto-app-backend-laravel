<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventParticipant extends Model
{
    protected $fillable = [
        'event_id',
        'name',
        'email',
        'phone',
        'notes',
        'checked_in',
        'checked_in_at'
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
