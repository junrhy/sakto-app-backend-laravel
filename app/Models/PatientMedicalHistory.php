<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientMedicalHistory extends Model
{
    protected $table = 'patient_medical_history';
    
    protected $fillable = [
        'client_identifier',
        'patient_id',
        'type',
        'condition_name',
        'description',
        'date_occurred',
        'icd10_code',
        'family_relationship',
        'age_at_diagnosis',
        'surgeon_name',
        'hospital_name',
        'complications',
        'status',
        'severity',
        'notes',
        'source'
    ];

    protected $casts = [
        'date_occurred' => 'date',
        'age_at_diagnosis' => 'integer'
    ];

    /**
     * Get the patient this medical history belongs to
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
     * Scope to filter by history type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get family history
     */
    public function scopeFamilyHistory($query)
    {
        return $query->where('type', 'family_history');
    }

    /**
     * Scope to get past illnesses
     */
    public function scopePastIllness($query)
    {
        return $query->where('type', 'past_illness');
    }

    /**
     * Scope to get surgeries
     */
    public function scopeSurgeries($query)
    {
        return $query->where('type', 'surgery');
    }

    /**
     * Scope to get active/chronic conditions
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['active', 'chronic']);
    }

    /**
     * Check if this is family history
     */
    public function isFamilyHistory()
    {
        return $this->type === 'family_history';
    }

    /**
     * Get formatted condition with date
     */
    public function getFormattedConditionAttribute()
    {
        $condition = $this->condition_name;
        if ($this->date_occurred) {
            $condition .= " ({$this->date_occurred->format('Y')})";
        }
        return $condition;
    }
}