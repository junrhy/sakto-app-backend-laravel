<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FnbReservation extends Model
{
    protected $fillable = [
        'name', 'date', 'time', 'guests', 'table_ids', 'notes', 'contact', 'status', 'client_identifier', 'confirmation_token', 'confirmed_at'
    ];

    protected $casts = [
        'table_ids' => 'array',
        'confirmed_at' => 'datetime',
    ];
}
