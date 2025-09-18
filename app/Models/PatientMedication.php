<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientMedication extends Model
{
    protected $fillable = [
        'client_identifier',
        'patient_id',
        'medication_name',
        'generic_name',
        'brand_name',
        'strength',
        'dosage_form',
        'dosage',
        'frequency',
        'route',
        'instructions',
        'start_date',
        'end_date',
        'duration_days',
        'as_needed',
        'indication',
        'prescribed_by',
        'prescriber_license',
        'prescription_date',
        'refills_remaining',
        'status',
        'medication_type',
        'ndc_code',
        'rxnorm_code',
        'side_effects_experienced',
        'notes',
        'adherence'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'prescription_date' => 'date',
        'as_needed' => 'boolean',
        'duration_days' => 'integer',
        'refills_remaining' => 'integer'
    ];

    /**
     * Get the patient this medication belongs to
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
     * Scope to get active medications
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to filter by medication type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('medication_type', $type);
    }

    /**
     * Scope to get current medications (active and not expired)
     */
    public function scopeCurrent($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            });
    }

    /**
     * Check if medication is currently active
     */
    public function isCurrent()
    {
        return $this->status === 'active' && 
               (is_null($this->end_date) || $this->end_date >= now());
    }

    /**
     * Get formatted medication display name
     */
    public function getFormattedNameAttribute()
    {
        $name = $this->medication_name;
        if ($this->strength) {
            $name .= " {$this->strength}";
        }
        if ($this->dosage_form) {
            $name .= " ({$this->dosage_form})";
        }
        return $name;
    }

    /**
     * Get formatted dosing instructions
     */
    public function getFormattedDosingAttribute()
    {
        $dosing = [];
        if ($this->dosage) $dosing[] = $this->dosage;
        if ($this->frequency) $dosing[] = $this->frequency;
        if ($this->route) $dosing[] = "via {$this->route}";
        if ($this->as_needed) $dosing[] = "(as needed)";
        
        return implode(' ', $dosing);
    }
}