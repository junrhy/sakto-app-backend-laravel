<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HandymanWorkOrderTimeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_identifier',
        'work_order_id',
        'technician_id',
        'started_at',
        'ended_at',
        'duration_minutes',
        'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function workOrder()
    {
        return $this->belongsTo(HandymanWorkOrder::class, 'work_order_id');
    }

    public function technician()
    {
        return $this->belongsTo(HandymanTechnician::class, 'technician_id');
    }
}

