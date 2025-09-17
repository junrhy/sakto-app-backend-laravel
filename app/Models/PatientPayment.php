<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientPayment extends Model
{
    protected $fillable = [
        'patient_id', 
        'payment_date', 
        'payment_amount', 
        'payment_method', 
        'payment_notes',
        'clinic_payment_account_id',
        'payment_type',
        'account_payment_reference',
        'covered_patients'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'payment_amount' => 'decimal:2',
        'payment_date' => 'date',
        'covered_patients' => 'array',
    ];

    /**
     * Payment type constants
     */
    public const PAYMENT_INDIVIDUAL = 'individual';
    public const PAYMENT_ACCOUNT = 'account';

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the clinic payment account for this payment
     */
    public function clinicPaymentAccount(): BelongsTo
    {
        return $this->belongsTo(ClinicPaymentAccount::class);
    }

    /**
     * Check if payment uses account-based payment
     */
    public function usesAccountPayment(): bool
    {
        return $this->payment_type === self::PAYMENT_ACCOUNT && $this->clinic_payment_account_id !== null;
    }

    /**
     * Generate account payment reference
     */
    public static function generateAccountPaymentReference(ClinicPaymentAccount $account): string
    {
        $date = now()->format('Ymd');
        $count = self::where('clinic_payment_account_id', $account->id)
                    ->whereDate('created_at', today())
                    ->count() + 1;
        
        return $account->account_code . '-PAY-' . $date . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Get the payment display information
     */
    public function getPaymentInfoAttribute(): array
    {
        if ($this->usesAccountPayment() && $this->clinicPaymentAccount) {
            return [
                'type' => 'account',
                'account_name' => $this->clinicPaymentAccount->account_name,
                'reference' => $this->account_payment_reference,
                'covered_patients_count' => $this->covered_patients ? count($this->covered_patients) : 1
            ];
        }
        
        return [
            'type' => 'individual',
            'patient_name' => $this->patient->name,
            'covered_patients_count' => 1
        ];
    }

    /**
     * Get patients covered by this payment
     */
    public function getCoveredPatientsAttribute($value): array
    {
        if ($this->usesAccountPayment() && $value) {
            return json_decode($value, true) ?: [];
        }
        
        return [$this->patient_id];
    }
}
