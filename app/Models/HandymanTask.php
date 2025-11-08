<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HandymanTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_identifier',
        'reference_number',
        'title',
        'description',
        'status',
        'priority',
        'scheduled_start_at',
        'scheduled_end_at',
        'location',
        'coordinates',
        'tags',
        'required_resources',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'scheduled_start_at' => 'datetime',
        'scheduled_end_at' => 'datetime',
        'coordinates' => 'array',
        'tags' => 'array',
        'required_resources' => 'array',
    ];

    public function assignments()
    {
        return $this->hasMany(HandymanTaskAssignment::class, 'task_id');
    }

    public function workOrders()
    {
        return $this->hasMany(HandymanWorkOrder::class, 'task_id');
    }
}

