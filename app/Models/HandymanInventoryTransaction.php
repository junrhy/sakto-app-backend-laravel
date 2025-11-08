<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HandymanInventoryTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_identifier',
        'inventory_item_id',
        'technician_id',
        'work_order_id',
        'transaction_type',
        'quantity',
        'details',
        'transaction_at',
        'recorded_by',
    ];

    protected $casts = [
        'details' => 'array',
        'transaction_at' => 'datetime',
    ];

    public function item()
    {
        return $this->belongsTo(HandymanInventoryItem::class, 'inventory_item_id');
    }

    public function technician()
    {
        return $this->belongsTo(HandymanTechnician::class, 'technician_id');
    }

    public function workOrder()
    {
        return $this->belongsTo(HandymanWorkOrder::class, 'work_order_id');
    }
}

