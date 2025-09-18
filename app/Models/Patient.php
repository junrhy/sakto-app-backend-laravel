<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\PatientBill;
use App\Models\PatientDentalChart;
use App\Models\PatientPayment;
use App\Models\PatientCheckup;
use App\Models\Appointment;
use App\Models\ClinicPaymentAccount;
use App\Models\PatientEncounter;
use App\Models\PatientVitalSigns;
use App\Models\PatientDiagnosis;
use App\Models\PatientAllergy;
use App\Models\PatientMedication;
use App\Models\PatientMedicalHistory;

class Patient extends Model
{
    protected $fillable = [
        'arn', 
        'medical_record_number',
        'name', 
        'birthdate', 
        'phone', 
        'email',
        'address',
        'city',
        'state', 
        'postal_code',
        'country',
        'gender',
        'blood_type',
        'medical_history',
        'allergies',
        'medications',
        'next_visit_date',
        'next_visit_time',
        'insurance_provider',
        'insurance_policy_number',
        'insurance_group_number',
        'insurance_expiration_date',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'emergency_contact_address',
        'emergency_contact_email',
        'smoking_status',
        'alcohol_use',
        'occupation',
        'preferred_language',
        'has_advance_directive',
        'advance_directive_notes',
        'status',
        'last_visit_date',
        'client_identifier',
        'clinic_payment_account_id',
        'billing_type'
    ];

    /**
     * Billing type constants
     */
    public const BILLING_INDIVIDUAL = 'individual';
    public const BILLING_ACCOUNT = 'account';

    public function bills(): HasMany
    {
        return $this->hasMany(PatientBill::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PatientPayment::class);
    }

    public function dentalChart(): HasMany
    {
        return $this->hasMany(PatientDentalChart::class);
    }

    public function checkups(): HasMany
    {
        return $this->hasMany(PatientCheckup::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Get all encounters for this patient
     */
    public function encounters(): HasMany
    {
        return $this->hasMany(PatientEncounter::class);
    }

    /**
     * Get all vital signs for this patient
     */
    public function vitalSigns(): HasMany
    {
        return $this->hasMany(PatientVitalSigns::class);
    }

    /**
     * Get all diagnoses for this patient
     */
    public function diagnoses(): HasMany
    {
        return $this->hasMany(PatientDiagnosis::class);
    }

    /**
     * Get all allergies for this patient
     */
    public function allergiesRecords(): HasMany
    {
        return $this->hasMany(PatientAllergy::class);
    }

    /**
     * Get all medications for this patient
     */
    public function medicationsRecords(): HasMany
    {
        return $this->hasMany(PatientMedication::class);
    }

    /**
     * Get all medical history for this patient
     */
    public function medicalHistoryRecords(): HasMany
    {
        return $this->hasMany(PatientMedicalHistory::class);
    }

    /**
     * Get active diagnoses
     */
    public function activeDiagnoses(): HasMany
    {
        return $this->diagnoses()->where('status', 'active');
    }

    /**
     * Get active allergies
     */
    public function activeAllergies(): HasMany
    {
        return $this->allergiesRecords()->where('status', 'active');
    }

    /**
     * Get current medications
     */
    public function currentMedications(): HasMany
    {
        return $this->medicationsRecords()->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', now());
            });
    }

    /**
     * Get recent encounters
     */
    public function recentEncounters($days = 30): HasMany
    {
        return $this->encounters()->where('encounter_datetime', '>=', now()->subDays($days));
    }

    /**
     * Get the clinic payment account for this patient
     */
    public function clinicPaymentAccount(): BelongsTo
    {
        return $this->belongsTo(ClinicPaymentAccount::class);
    }

    /**
     * Check if patient uses account-based billing
     */
    public function usesAccountBilling(): bool
    {
        return $this->billing_type === self::BILLING_ACCOUNT && $this->clinic_payment_account_id !== null;
    }

    /**
     * Get the billing display name (individual patient name or account name)
     */
    public function getBillingDisplayNameAttribute(): string
    {
        if ($this->usesAccountBilling() && $this->clinicPaymentAccount) {
            return $this->clinicPaymentAccount->account_name . ' (' . $this->name . ')';
        }
        
        return $this->name;
    }
}
