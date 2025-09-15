<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FnbBlockedDate extends Model
{
    protected $fillable = [
        'blocked_date',
        'timeslots',
        'reason',
        'client_identifier'
    ];

    protected $casts = [
        'blocked_date' => 'date',
        'timeslots' => 'array',
    ];
}
