<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobApplicant extends Model
{
    protected $fillable = [
        'client_identifier',
        'name',
        'email',
        'phone',
        'address',
        'linkedin_url',
        'portfolio_url',
        'work_experience',
        'education',
        'skills',
        'certifications',
        'languages',
        'summary',
        'notes',
    ];

    /**
     * Get all applications from this applicant
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
     * Scope to find by email
     */
    public function scopeByEmail($query, $email)
    {
        return $query->where('email', $email);
    }

    /**
     * Find or create applicant by email
     */
    public static function findOrCreateByEmail($clientIdentifier, $email, $data = [])
    {
        $applicant = self::where('client_identifier', $clientIdentifier)
            ->where('email', $email)
            ->first();

        if (!$applicant) {
            $applicant = self::create(array_merge([
                'client_identifier' => $clientIdentifier,
                'email' => $email,
            ], $data));
        }

        return $applicant;
    }
}
