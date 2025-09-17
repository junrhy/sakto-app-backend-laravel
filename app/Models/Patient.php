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

class Patient extends Model
{
    protected $fillable = [
        'arn', 
        'name', 
        'birthdate', 
        'phone', 
        'email', 
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
