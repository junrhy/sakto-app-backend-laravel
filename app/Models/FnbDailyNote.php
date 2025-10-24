<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FnbDailyNote extends Model
{
    protected $fillable = [
        'client_identifier',
        'note_date',
        'note',
        'created_by',
    ];

    protected $casts = [
        'note_date' => 'date',
    ];
}
