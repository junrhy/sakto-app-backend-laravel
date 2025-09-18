<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientVitalSigns extends Model
{
    protected $fillable = [
        'client_identifier',
        'patient_id',
        'encounter_id',
        'measured_at',
        'systolic_bp',
        'diastolic_bp',
        'bp_position',
        'bp_cuff_size',
        'heart_rate',
        'heart_rhythm',
        'respiratory_rate',
        'breathing_quality',
        'temperature',
        'temperature_unit',
        'temperature_route',
        'oxygen_saturation',
        'on_oxygen',
        'oxygen_flow_rate',
        'weight',
        'height',
        'bmi',
        'head_circumference',
        'pain_score',
        'pain_location',
        'pain_quality',
        'glucose_level',
        'glucose_test_type',
        'measured_by',
        'measurement_method',
        'notes',
        'flagged_abnormal',
        'abnormal_notes'
    ];

    protected $casts = [
        'measured_at' => 'datetime',
        'systolic_bp' => 'decimal:2',
        'diastolic_bp' => 'decimal:2',
        'heart_rate' => 'decimal:2',
        'respiratory_rate' => 'decimal:2',
        'temperature' => 'decimal:2',
        'oxygen_saturation' => 'decimal:2',
        'on_oxygen' => 'boolean',
        'weight' => 'decimal:3',
        'height' => 'decimal:3',
        'bmi' => 'decimal:2',
        'head_circumference' => 'decimal:3',
        'pain_score' => 'integer',
        'glucose_level' => 'decimal:3',
        'flagged_abnormal' => 'boolean'
    ];

    /**
     * Get the patient these vital signs belong to
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the encounter these vital signs were recorded during
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
     * Scope to get recent vital signs
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('measured_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to get flagged abnormal results
     */
    public function scopeAbnormal($query)
    {
        return $query->where('flagged_abnormal', true);
    }

    /**
     * Get formatted blood pressure
     */
    public function getFormattedBloodPressureAttribute()
    {
        if ($this->systolic_bp && $this->diastolic_bp) {
            return "{$this->systolic_bp}/{$this->diastolic_bp} mmHg";
        }
        return null;
    }

    /**
     * Get formatted temperature with unit
     */
    public function getFormattedTemperatureAttribute()
    {
        if ($this->temperature) {
            $unit = $this->temperature_unit === 'fahrenheit' ? '°F' : '°C';
            return "{$this->temperature}{$unit}";
        }
        return null;
    }

    /**
     * Calculate BMI if weight and height are provided
     */
    public function calculateBmi()
    {
        if ($this->weight && $this->height) {
            // Convert height from cm to meters
            $heightInMeters = $this->height / 100;
            $bmi = $this->weight / ($heightInMeters * $heightInMeters);
            $this->bmi = round($bmi, 2);
            return $this->bmi;
        }
        return null;
    }

    /**
     * Check if vital signs are within normal ranges
     */
    public function checkNormalRanges()
    {
        $abnormal = false;
        $notes = [];

        // Blood pressure checks
        if ($this->systolic_bp && ($this->systolic_bp > 140 || $this->systolic_bp < 90)) {
            $abnormal = true;
            $notes[] = 'Abnormal systolic blood pressure';
        }
        if ($this->diastolic_bp && ($this->diastolic_bp > 90 || $this->diastolic_bp < 60)) {
            $abnormal = true;
            $notes[] = 'Abnormal diastolic blood pressure';
        }

        // Heart rate checks
        if ($this->heart_rate && ($this->heart_rate > 100 || $this->heart_rate < 60)) {
            $abnormal = true;
            $notes[] = 'Abnormal heart rate';
        }

        // Temperature checks (assuming Celsius)
        if ($this->temperature) {
            $temp = $this->temperature_unit === 'fahrenheit' ? 
                ($this->temperature - 32) * 5/9 : $this->temperature;
            if ($temp > 37.5 || $temp < 36.0) {
                $abnormal = true;
                $notes[] = 'Abnormal temperature';
            }
        }

        // Oxygen saturation checks
        if ($this->oxygen_saturation && $this->oxygen_saturation < 95) {
            $abnormal = true;
            $notes[] = 'Low oxygen saturation';
        }

        $this->flagged_abnormal = $abnormal;
        if ($abnormal) {
            $this->abnormal_notes = implode('; ', $notes);
        }

        return $abnormal;
    }

    /**
     * Boot method to auto-calculate BMI and check ranges
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($vitalSigns) {
            $vitalSigns->calculateBmi();
            $vitalSigns->checkNormalRanges();
        });
    }
}