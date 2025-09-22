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
        'billing_type',
        // VIP fields
        'is_vip',
        'vip_tier',
        'vip_since',
        'vip_discount_percentage',
        'vip_notes',
        'priority_scheduling',
        'extended_consultation_time',
        'dedicated_staff_assignment',
        'complimentary_services'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'birthdate' => 'date',
        'insurance_expiration_date' => 'date',
        'has_advance_directive' => 'boolean',
        'last_visit_date' => 'date',
        'is_vip' => 'boolean',
        'vip_since' => 'datetime',
        'vip_discount_percentage' => 'decimal:2',
        'priority_scheduling' => 'boolean',
        'extended_consultation_time' => 'boolean',
        'dedicated_staff_assignment' => 'boolean',
        'complimentary_services' => 'boolean',
    ];

    /**
     * Billing type constants
     */
    public const BILLING_INDIVIDUAL = 'individual';
    public const BILLING_ACCOUNT = 'account';

    /**
     * VIP tier constants
     */
    public const VIP_TIER_STANDARD = 'standard';
    public const VIP_TIER_GOLD = 'gold';
    public const VIP_TIER_PLATINUM = 'platinum';
    public const VIP_TIER_DIAMOND = 'diamond';

    /**
     * VIP tier benefits configuration
     */
    public static function getVipTierBenefits(): array
    {
        return [
            self::VIP_TIER_STANDARD => [
                'name' => 'Standard',
                'discount_percentage' => 0.00,
                'priority_scheduling' => false,
                'extended_consultation_time' => false,
                'dedicated_staff_assignment' => false,
                'complimentary_services' => false,
                'color' => 'gray',
                'icon' => '⭐',
                'description' => 'Regular patient status'
            ],
            self::VIP_TIER_GOLD => [
                'name' => 'Gold VIP',
                'discount_percentage' => 5.00,
                'priority_scheduling' => true,
                'extended_consultation_time' => false,
                'dedicated_staff_assignment' => false,
                'complimentary_services' => false,
                'color' => 'yellow',
                'icon' => '🥇',
                'description' => 'Priority scheduling + 5% discount'
            ],
            self::VIP_TIER_PLATINUM => [
                'name' => 'Platinum VIP',
                'discount_percentage' => 10.00,
                'priority_scheduling' => true,
                'extended_consultation_time' => true,
                'dedicated_staff_assignment' => false,
                'complimentary_services' => true,
                'color' => 'blue',
                'icon' => '💎',
                'description' => 'Extended consultation + 10% discount + complimentary services'
            ],
            self::VIP_TIER_DIAMOND => [
                'name' => 'Diamond VIP',
                'discount_percentage' => 15.00,
                'priority_scheduling' => true,
                'extended_consultation_time' => true,
                'dedicated_staff_assignment' => true,
                'complimentary_services' => true,
                'color' => 'purple',
                'icon' => '👑',
                'description' => 'Full VIP experience + 15% discount + dedicated staff'
            ]
        ];
    }

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

    /**
     * Check if patient is VIP
     */
    public function isVip(): bool
    {
        return $this->is_vip === true;
    }

    /**
     * Get VIP tier benefits
     */
    public function getVipBenefits(): array
    {
        $benefits = self::getVipTierBenefits();
        return $benefits[$this->vip_tier] ?? $benefits[self::VIP_TIER_STANDARD];
    }

    /**
     * Get VIP tier display information
     */
    public function getVipTierDisplayAttribute(): array
    {
        $benefits = $this->getVipBenefits();
        return [
            'name' => $benefits['name'],
            'icon' => $benefits['icon'],
            'color' => $benefits['color'],
            'description' => $benefits['description']
        ];
    }

    /**
     * Calculate discount amount for a given bill amount
     */
    public function calculateVipDiscount(float $amount): float
    {
        if (!$this->isVip()) {
            return 0.00;
        }

        $discountPercentage = $this->vip_discount_percentage > 0 
            ? $this->vip_discount_percentage 
            : $this->getVipBenefits()['discount_percentage'];

        return ($amount * $discountPercentage) / 100;
    }

    /**
     * Get final amount after VIP discount
     */
    public function applyVipDiscount(float $amount): float
    {
        $discount = $this->calculateVipDiscount($amount);
        return $amount - $discount;
    }

    /**
     * Check if patient has priority scheduling privilege
     */
    public function hasPriorityScheduling(): bool
    {
        return $this->isVip() && ($this->priority_scheduling || $this->getVipBenefits()['priority_scheduling']);
    }

    /**
     * Check if patient has extended consultation time privilege
     */
    public function hasExtendedConsultationTime(): bool
    {
        return $this->isVip() && ($this->extended_consultation_time || $this->getVipBenefits()['extended_consultation_time']);
    }

    /**
     * Check if patient has dedicated staff assignment privilege
     */
    public function hasDedicatedStaffAssignment(): bool
    {
        return $this->isVip() && ($this->dedicated_staff_assignment || $this->getVipBenefits()['dedicated_staff_assignment']);
    }

    /**
     * Check if patient has complimentary services privilege
     */
    public function hasComplimentaryServices(): bool
    {
        return $this->isVip() && ($this->complimentary_services || $this->getVipBenefits()['complimentary_services']);
    }

    /**
     * Promote patient to VIP status
     */
    public function promoteToVip(string $tier = self::VIP_TIER_GOLD, array $customBenefits = []): void
    {
        $this->is_vip = true;
        $this->vip_tier = $tier;
        $this->vip_since = now();
        
        // Apply tier benefits or custom benefits
        $benefits = $customBenefits ?: $this->getVipTierBenefits()[$tier];
        
        if (isset($benefits['discount_percentage'])) {
            $this->vip_discount_percentage = $benefits['discount_percentage'];
        }
        if (isset($benefits['priority_scheduling'])) {
            $this->priority_scheduling = $benefits['priority_scheduling'];
        }
        if (isset($benefits['extended_consultation_time'])) {
            $this->extended_consultation_time = $benefits['extended_consultation_time'];
        }
        if (isset($benefits['dedicated_staff_assignment'])) {
            $this->dedicated_staff_assignment = $benefits['dedicated_staff_assignment'];
        }
        if (isset($benefits['complimentary_services'])) {
            $this->complimentary_services = $benefits['complimentary_services'];
        }
        
        $this->save();
    }

    /**
     * Remove VIP status
     */
    public function removeVipStatus(): void
    {
        $this->is_vip = false;
        $this->vip_tier = self::VIP_TIER_STANDARD;
        $this->vip_since = null;
        $this->vip_discount_percentage = 0.00;
        $this->vip_notes = null;
        $this->priority_scheduling = false;
        $this->extended_consultation_time = false;
        $this->dedicated_staff_assignment = false;
        $this->complimentary_services = false;
        
        $this->save();
    }
}
