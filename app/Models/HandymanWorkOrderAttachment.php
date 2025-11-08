<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HandymanWorkOrderAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_identifier',
        'work_order_id',
        'file_path',
        'file_type',
        'thumbnail_path',
        'uploaded_by',
        'description',
    ];

    public function workOrder()
    {
        return $this->belongsTo(HandymanWorkOrder::class, 'work_order_id');
    }
}

