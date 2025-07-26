<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductReviewReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'review_id',
        'reporter_name',
        'reason',
        'comment',
        'status',
    ];

    public function review()
    {
        return $this->belongsTo(\App\Models\ProductReview::class, 'review_id');
    }
} 