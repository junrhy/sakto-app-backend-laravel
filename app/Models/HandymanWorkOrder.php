<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HandymanWorkOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_identifier',
        'reference_number',
        'status',
        'task_id',
        'technician_id',
        'customer_name',
        'customer_contact',
        'customer_address',
        'scope_of_work',
        'materials',
        'checklist',
        'approval',
        'scheduled_at',
        'started_at',
        'completed_at',
        'notes',
        'created_by',
        'approved_by',
    ];

    protected $casts = [
        'materials' => 'array',
        'checklist' => 'array',
        'approval' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function task()
    {
        return $this->belongsTo(HandymanTask::class, 'task_id');
    }

    public function technician()
    {
        return $this->belongsTo(HandymanTechnician::class, 'technician_id');
    }

    public function timeLogs()
    {
        return $this->hasMany(HandymanWorkOrderTimeLog::class, 'work_order_id');
    }

    public function attachments()
    {
        return $this->hasMany(HandymanWorkOrderAttachment::class, 'work_order_id');
    }

    public function inventoryTransactions()
    {
        return $this->hasMany(HandymanInventoryTransaction::class, 'work_order_id');
    }
}

