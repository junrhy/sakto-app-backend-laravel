<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientDiagnosis extends Model
{
    protected $fillable = [
        'client_identifier',
        'patient_id',
        'encounter_id',
        'diagnosis_name',
        'diagnosis_description',
        'icd10_code',
        'snomed_code',
        'diagnosis_type',
        'category',
        'onset_date',
        'diagnosis_date',
        'resolution_date',
        'diagnosed_by',
        'severity',
        'status',
        'clinical_notes',
        'body_site',
        'laterality',
        'verification_status',
        'confidence_level',
        'treatment_plan',
        'complications',
        'outcome_notes',
        'next_review_date',
        'requires_monitoring',
        'monitoring_notes'
    ];

    protected $casts = [
        'onset_date' => 'date',
        'diagnosis_date' => 'date',
        'resolution_date' => 'date',
        'next_review_date' => 'date',
        'confidence_level' => 'integer',
        'requires_monitoring' => 'boolean'
    ];

    /**
     * Get the patient this diagnosis belongs to
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the encounter this diagnosis was made during
     */
    public function encounter(): BelongsTo
    {
        return $this->belongsTo(PatientEncounter::class, 'encounter_id');
    }

    /**
     * Scope to filter by client identifier
     */
    public function scopeForClient($query, $clientIdentifier)
    {
        return $query->where('client_identifier', $clientIdentifier);
    }

    /**
     * Scope to filter by diagnosis type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('diagnosis_type', $type);
    }

    /**
     * Scope to filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get active diagnoses
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get primary diagnoses
     */
    public function scopePrimary($query)
    {
        return $query->where('diagnosis_type', 'primary');
    }

    /**
     * Scope to get chronic conditions
     */
    public function scopeChronic($query)
    {
        return $query->where('category', 'chronic');
    }

    /**
     * Scope to get diagnoses requiring monitoring
     */
    public function scopeRequiresMonitoring($query)
    {
        return $query->where('requires_monitoring', true);
    }

    /**
     * Scope to get diagnoses due for review
     */
    public function scopeDueForReview($query)
    {
        return $query->where('next_review_date', '<=', now())
            ->whereNotNull('next_review_date');
    }

    /**
     * Get formatted diagnosis with ICD-10 code
     */
    public function getFormattedDiagnosisAttribute()
    {
        $diagnosis = $this->diagnosis_name;
        if ($this->icd10_code) {
            $diagnosis .= " ({$this->icd10_code})";
        }
        return $diagnosis;
    }

    /**
     * Get diagnosis duration in human readable format
     */
    public function getDurationAttribute()
    {
        if (!$this->onset_date) {
            return null;
        }

        $endDate = $this->resolution_date ?? now();
        $duration = $this->onset_date->diffInDays($endDate);

        if ($duration < 30) {
            return "{$duration} days";
        } elseif ($duration < 365) {
            $months = round($duration / 30);
            return "{$months} months";
        } else {
            $years = round($duration / 365, 1);
            return "{$years} years";
        }
    }

    /**
     * Check if diagnosis is resolved
     */
    public function isResolved()
    {
        return $this->status === 'resolved' && $this->resolution_date;
    }

    /**
     * Check if diagnosis is chronic
     */
    public function isChronic()
    {
        return $this->category === 'chronic';
    }

    /**
     * Check if diagnosis is active
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Get severity level as numeric value for sorting
     */
    public function getSeverityLevelAttribute()
    {
        $levels = [
            'mild' => 1,
            'moderate' => 2,
            'severe' => 3,
            'critical' => 4,
            'unknown' => 0
        ];

        return $levels[$this->severity] ?? 0;
    }

    /**
     * Scope to order by severity (most severe first)
     */
    public function scopeOrderBySeverity($query)
    {
        return $query->orderByRaw("
            CASE severity
                WHEN 'critical' THEN 4
                WHEN 'severe' THEN 3
                WHEN 'moderate' THEN 2
                WHEN 'mild' THEN 1
                ELSE 0
            END DESC
        ");
    }

    /**
     * Scope to order by diagnosis type priority (primary first)
     */
    public function scopeOrderByType($query)
    {
        return $query->orderByRaw("
            CASE diagnosis_type
                WHEN 'primary' THEN 1
                WHEN 'secondary' THEN 2
                WHEN 'confirmed' THEN 3
                WHEN 'provisional' THEN 4
                WHEN 'differential' THEN 5
                WHEN 'rule_out' THEN 6
                ELSE 7
            END
        ");
    }
}