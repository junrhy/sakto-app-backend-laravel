<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    protected $fillable = [
        'client_identifier',
        'job_id',
        'applicant_id',
        'cover_letter',
        'status',
        'notes',
        'applied_at',
        'reviewed_at',
        'interview_date',
    ];

    protected $casts = [
        'applied_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'interview_date' => 'datetime',
    ];

    /**
     * Get the job for this application
     */
    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    /**
     * Get the applicant for this application
     */
    public function applicant()
    {
        return $this->belongsTo(JobApplicant::class);
    }

    /**
     * Scope to filter by client identifier
     */
    public function scopeForClient($query, $clientIdentifier)
    {
        return $query->where('client_identifier', $clientIdentifier);
    }

    /**
     * Scope to filter by job
     */
    public function scopeByJob($query, $jobId)
    {
        return $query->where('job_id', $jobId);
    }

    /**
     * Scope to filter by applicant
     */
    public function scopeByApplicant($query, $applicantId)
    {
        return $query->where('applicant_id', $applicantId);
    }

    /**
     * Scope to filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Mark application as reviewed
     */
    public function markAsReviewed()
    {
        $this->update([
            'status' => 'reviewed',
            'reviewed_at' => now(),
        ]);
    }
}
