<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'gender',
        'fathers_name',
        'mothers_maiden_name',
        'email',
        'call_number',
        'sms_number',
        'whatsapp',
        'facebook',
        'instagram',
        'twitter',
        'linkedin',
        'address',
        'notes',
        'id_picture',
        'id_numbers',
        'client_identifier',
    ];
}
