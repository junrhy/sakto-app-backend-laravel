<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    protected $fillable = [
        'client_identifier',
        'job_board_id',
        'title',
        'description',
        'requirements',
        'salary_min',
        'salary_max',
        'salary_currency',
        'location',
        'employment_type',
        'job_category',
        'status',
        'application_deadline',
        'application_url',
        'application_email',
        'is_featured',
        'views_count',
        'applications_count',
    ];

    protected $casts = [
        'salary_min' => 'decimal:2',
        'salary_max' => 'decimal:2',
        'is_featured' => 'boolean',
        'views_count' => 'integer',
        'applications_count' => 'integer',
        'application_deadline' => 'date',
    ];

    /**
     * Get the job board that owns this job
     */
    public function jobBoard()
    {
        return $this->belongsTo(JobBoard::class);
    }

    /**
     * Get all applications for this job
     */
    public function applications()
    {
        return $this->hasMany(JobApplication::class);
    }

    /**
     * Scope to filter by client identifier
     */
    public function scopeForClient($query, $clientIdentifier)
    {
        return $query->where('client_identifier', $clientIdentifier);
    }

    /**
     * Scope to filter by job board
     */
    public function scopeByJobBoard($query, $jobBoardId)
    {
        return $query->where('job_board_id', $jobBoardId);
    }

    /**
     * Scope to filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get published jobs
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope to get featured jobs
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Increment views count
     */
    public function incrementViews()
    {
        $this->increment('views_count');
    }

    /**
     * Increment applications count
     */
    public function incrementApplications()
    {
        $this->increment('applications_count');
    }

    /**
     * Decrement applications count
     */
    public function decrementApplications()
    {
        $this->decrement('applications_count');
    }
}
