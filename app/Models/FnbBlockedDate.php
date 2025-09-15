<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FnbBlockedDate extends Model
{
    protected $fillable = [
        'blocked_date',
        'start_time',
        'end_time',
        'is_full_day',
        'reason',
        'client_identifier'
    ];

    protected $casts = [
        'blocked_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'is_full_day' => 'boolean',
    ];
}
