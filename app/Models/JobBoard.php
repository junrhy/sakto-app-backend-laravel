<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobBoard extends Model
{
    protected $fillable = [
        'client_identifier',
        'name',
        'description',
        'slug',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * Get all jobs for this job board
     */
    public function jobs()
    {
        return $this->hasMany(Job::class);
    }

    /**
     * Get published jobs for this job board
     */
    public function publishedJobs()
    {
        return $this->hasMany(Job::class)->where('status', 'published');
    }

    /**
     * Scope to filter by client identifier
     */
    public function scopeForClient($query, $clientIdentifier)
    {
        return $query->where('client_identifier', $clientIdentifier);
    }

    /**
     * Scope to get only active job boards
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
