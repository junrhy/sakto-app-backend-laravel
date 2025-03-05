<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientDetails extends Model
{
    protected $fillable = [
        'client_id',
        'app_name',
        'name',
        'value',
        'client_identifier',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
