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
        'notes'
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
