<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Content extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'status',
        'featured_image',
        'author',
        'client_identifier',
        'meta_title',
        'meta_description',
        'tags',
        'categories',
        'scheduled_at',
        'published_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'categories' => 'array',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeByClient($query, $clientIdentifier)
    {
        return $query->where('client_identifier', $clientIdentifier);
    }
} 