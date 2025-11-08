<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HandymanTechnician extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_identifier',
        'name',
        'email',
        'phone',
        'specialty',
        'skills',
        'status',
        'location',
        'current_load',
    ];

    protected $casts = [
        'skills' => 'array',
    ];

    public function assignments()
    {
        return $this->hasMany(HandymanTaskAssignment::class, 'technician_id');
    }

    public function workOrders()
    {
        return $this->hasMany(HandymanWorkOrder::class, 'technician_id');
    }

    public function inventoryTransactions()
    {
        return $this->hasMany(HandymanInventoryTransaction::class, 'technician_id');
    }
}

