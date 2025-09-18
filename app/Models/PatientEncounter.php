<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatientEncounter extends Model
{
    protected $fillable = [
        'client_identifier',
        'patient_id',
        'encounter_number',
        'encounter_datetime',
        'end_datetime',
        'encounter_type',
        'encounter_class',
        'location',
        'room_number',
        'attending_provider',
        'referring_provider',
        'chief_complaint',
        'history_present_illness',
        'review_of_systems',
        'physical_examination',
        'laboratory_results',
        'diagnostic_results',
        'clinical_impression',
        'differential_diagnosis',
        'treatment_plan',
        'medications_prescribed',
        'procedures_ordered',
        'follow_up_instructions',
        'next_appointment_date',
        'clinical_guidelines_followed',
        'decision_rationale',
        'patient_education_provided',
        'patient_understanding_level',
        'interpreter_used',
        'interpreter_language',
        'status',
        'priority',
        'patient_satisfaction_score',
        'patient_feedback',
        'encounter_duration_minutes',
        'insurance_authorization',
        'billing_notes',
        'requires_follow_up',
        'documented_by',
        'documentation_completed_at',
        'documentation_complete',
        'additional_notes',
        'care_coordination_notes'
    ];

    protected $casts = [
        'encounter_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'next_appointment_date' => 'date',
        'interpreter_used' => 'boolean',
        'patient_satisfaction_score' => 'integer',
        'encounter_duration_minutes' => 'integer',
        'requires_follow_up' => 'boolean',
        'documentation_completed_at' => 'datetime',
        'documentation_complete' => 'boolean'
    ];

    /**
     * Get the patient this encounter belongs to
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the vital signs recorded during this encounter
     */
    public function vitalSigns(): HasMany
    {
        return $this->hasMany(PatientVitalSigns::class, 'encounter_id');
    }

    /**
     * Get the diagnoses made during this encounter
     */
    public function diagnoses(): HasMany
    {
        return $this->hasMany(PatientDiagnosis::class, 'encounter_id');
    }

    /**
     * Scope to filter by client identifier
     */
    public function scopeForClient($query, $clientIdentifier)
    {
        return $query->where('client_identifier', $clientIdentifier);
    }

    /**
     * Scope to filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by encounter type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('encounter_type', $type);
    }

    /**
     * Scope to get recent encounters
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('encounter_datetime', '>=', now()->subDays($days));
    }

    /**
     * Get encounter duration in human readable format
     */
    public function getFormattedDurationAttribute()
    {
        if (!$this->encounter_duration_minutes) {
            return 'N/A';
        }
        
        $hours = floor($this->encounter_duration_minutes / 60);
        $minutes = $this->encounter_duration_minutes % 60;
        
        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }
        
        return "{$minutes}m";
    }

    /**
     * Check if encounter is completed
     */
    public function isCompleted()
    {
        return $this->status === 'completed' && $this->documentation_complete;
    }

    /**
     * Generate unique encounter number
     */
    public static function generateEncounterNumber($clientIdentifier)
    {
        $date = now()->format('Ymd');
        $sequence = static::where('client_identifier', $clientIdentifier)
            ->where('encounter_number', 'LIKE', "ENC-{$date}-%")
            ->count() + 1;
        
        return "ENC-{$date}-" . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}