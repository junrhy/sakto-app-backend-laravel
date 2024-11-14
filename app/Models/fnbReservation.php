<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class fnbReservation extends Model
{
    protected $fillable = [
        'name', 'date', 'time', 'guests', 'table_id', 'notes', 'contact', 'status', 'client_identifier'
    ];
}
