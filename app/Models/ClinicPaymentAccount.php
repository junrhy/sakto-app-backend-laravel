<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClinicPaymentAccount extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'client_identifier',
        'account_type',
        'account_name',
        'account_code',
        'description',
        'contact_person',
        'contact_email',
        'contact_phone',
        'address',
        'credit_limit',
        'status',
        'billing_settings',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'credit_limit' => 'decimal:2',
        'billing_settings' => 'array',
    ];

    /**
     * Account type constants
     */
    public const TYPE_GROUP = 'group';
    public const TYPE_COMPANY = 'company';

    /**
     * Status constants
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_SUSPENDED = 'suspended';

    /**
     * Get all patients associated with this account
     */
    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }

    /**
     * Get all bills associated with this account
     */
    public function bills(): HasMany
    {
        return $this->hasMany(PatientBill::class);
    }

    /**
     * Get all payments associated with this account
     */
    public function payments(): HasMany
    {
        return $this->hasMany(PatientPayment::class);
    }

    /**
     * Get the total outstanding balance for this account
     */
    public function getTotalOutstandingAttribute(): float
    {
        // If bills and payments relationships are loaded, use them for efficiency
        if ($this->relationLoaded('bills') && $this->relationLoaded('payments')) {
            $totalBills = $this->bills->sum('bill_amount');
            $totalPayments = $this->payments->sum('payment_amount');
        } else {
            // Otherwise, query the database
            $totalBills = $this->bills()->sum('bill_amount');
            $totalPayments = $this->payments()->sum('payment_amount');
        }
        
        return max(0, $totalBills - $totalPayments);
    }

    /**
     * Check if account is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Generate unique account code
     */
    public static function generateAccountCode(string $type, string $name): string
    {
        $prefix = strtoupper(substr($type, 0, 3)); // GRO or COM
        $nameCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 5));
        $timestamp = now()->format('ymd');
        
        return $prefix . '-' . $nameCode . '-' . $timestamp;
    }
}
