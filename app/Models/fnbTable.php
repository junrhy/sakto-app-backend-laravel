<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FnbTable extends Model
{
    use HasFactory;

    protected $table = 'fnb_tables';

    protected $fillable = [
        'name',
        'seats',
        'status'
    ];

    protected $casts = [
        'seats' => 'integer',
        'status' => 'string'
    ];
}
