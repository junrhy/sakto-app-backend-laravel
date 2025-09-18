<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientAllergy extends Model
{
    protected $fillable = [
        'client_identifier',
        'patient_id',
        'allergen',
        'allergen_type',
        'reaction_description',
        'severity',
        'symptoms',
        'first_occurrence_date',
        'last_occurrence_date',
        'onset_time',
        'status',
        'verification_status',
        'notes',
        'reported_by',
        'verified_date',
        'verified_by'
    ];

    protected $casts = [
        'symptoms' => 'json',
        'first_occurrence_date' => 'date',
        'last_occurrence_date' => 'date',
        'verified_date' => 'datetime'
    ];

    /**
     * Get the patient this allergy belongs to
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Scope to filter by client identifier
     */
    public function scopeForClient($query, $clientIdentifier)
    {
        return $query->where('client_identifier', $clientIdentifier);
    }

    /**
     * Scope to get active allergies
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to filter by allergen type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('allergen_type', $type);
    }

    /**
     * Scope to filter by severity
     */
    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope to get life-threatening allergies
     */
    public function scopeLifeThreatening($query)
    {
        return $query->where('severity', 'life_threatening');
    }

    /**
     * Check if allergy is life-threatening
     */
    public function isLifeThreatening()
    {
        return $this->severity === 'life_threatening';
    }

    /**
     * Get formatted symptoms list
     */
    public function getFormattedSymptomsAttribute()
    {
        if (is_array($this->symptoms)) {
            return implode(', ', $this->symptoms);
        }
        return $this->symptoms;
    }
}