<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FnbReservation extends Model
{
    protected $fillable = [
        'name', 'date', 'time', 'guests', 'table_id', 'notes', 'contact', 'status', 'client_identifier'
    ];
}
