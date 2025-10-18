<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FnbOpenedDate extends Model
{
    protected $fillable = [
        'client_identifier',
        'opened_date',
        'timeslots',
        'reason',
    ];

    protected $casts = [
        'timeslots' => 'array',
        'opened_date' => 'date',
    ];
}
