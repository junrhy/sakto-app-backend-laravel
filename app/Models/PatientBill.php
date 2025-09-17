<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientBill extends Model
{
    protected $fillable = [
        'patient_id', 
        'bill_number', 
        'bill_date', 
        'bill_amount', 
        'bill_status', 
        'bill_details',
        'clinic_payment_account_id',
        'billing_type',
        'account_bill_reference'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'bill_amount' => 'decimal:2',
        'bill_date' => 'date',
    ];

    /**
     * Billing type constants
     */
    public const BILLING_INDIVIDUAL = 'individual';
    public const BILLING_ACCOUNT = 'account';

    /**
     * Bill status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_CANCELLED = 'cancelled';

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the clinic payment account for this bill
     */
    public function clinicPaymentAccount(): BelongsTo
    {
        return $this->belongsTo(ClinicPaymentAccount::class);
    }

    /**
     * Check if bill uses account-based billing
     */
    public function usesAccountBilling(): bool
    {
        return $this->billing_type === self::BILLING_ACCOUNT && $this->clinic_payment_account_id !== null;
    }

    /**
     * Generate account bill reference
     */
    public static function generateAccountBillReference(ClinicPaymentAccount $account): string
    {
        $date = now()->format('Ymd');
        $count = self::where('clinic_payment_account_id', $account->id)
                    ->whereDate('created_at', today())
                    ->count() + 1;
        
        return $account->account_code . '-BILL-' . $date . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Get the billing display information
     */
    public function getBillingInfoAttribute(): array
    {
        if ($this->usesAccountBilling() && $this->clinicPaymentAccount) {
            return [
                'type' => 'account',
                'account_name' => $this->clinicPaymentAccount->account_name,
                'patient_name' => $this->patient->name,
                'reference' => $this->account_bill_reference
            ];
        }
        
        return [
            'type' => 'individual',
            'patient_name' => $this->patient->name,
            'reference' => $this->bill_number
        ];
    }
}
