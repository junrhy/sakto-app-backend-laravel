<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'name',
        'client_identifier',
        'email',
        'contact_number',
        'referrer',
        'active'
    ];
}
