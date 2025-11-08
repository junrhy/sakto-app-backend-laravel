<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HandymanTaskAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_identifier',
        'task_id',
        'technician_id',
        'assigned_start_at',
        'assigned_end_at',
        'is_primary',
        'conflict_status',
    ];

    protected $casts = [
        'assigned_start_at' => 'datetime',
        'assigned_end_at' => 'datetime',
        'is_primary' => 'boolean',
    ];

    public function task()
    {
        return $this->belongsTo(HandymanTask::class, 'task_id');
    }

    public function technician()
    {
        return $this->belongsTo(HandymanTechnician::class, 'technician_id');
    }
}

