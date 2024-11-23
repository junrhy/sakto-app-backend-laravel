<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    protected $fillable = ['name', 'email', 'position', 'salary', 'status', 'client_identifier'];
}
