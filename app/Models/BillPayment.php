<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillPayment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'bill_number',
        'bill_title',
        'bill_description',
        'biller_id',
        'amount',
        'due_date',
        'payment_date',
        'status',
        'payment_method',
        'reference_number',
        'notes',
        'email',
        'contact_number',
        'customer_name',
        'client_identifier',
        'category',
        'priority',
        'is_recurring',
        'recurring_frequency',
        'next_due_date',
        'attachments',
        'reminder_sent',
        'reminder_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'payment_date' => 'date',
        'next_due_date' => 'date',
        'reminder_date' => 'date',
        'is_recurring' => 'boolean',
        'reminder_sent' => 'boolean',
        'attachments' => 'array',
    ];

    /**
     * The possible status values.
     *
     * @var array<string>
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_PARTIAL = 'partial';

    /**
     * The possible priority values.
     *
     * @var array<string>
     */
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    /**
     * The possible recurring frequency values.
     *
     * @var array<string>
     */
    public const FREQUENCY_DAILY = 'daily';
    public const FREQUENCY_WEEKLY = 'weekly';
    public const FREQUENCY_MONTHLY = 'monthly';
    public const FREQUENCY_QUARTERLY = 'quarterly';
    public const FREQUENCY_YEARLY = 'yearly';

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($billPayment) {
            if (!$billPayment->bill_number) {
                $billPayment->bill_number = self::generateBillNumber();
            }
        });
    }

    /**
     * Generate a unique bill number.
     */
    public static function generateBillNumber(): string
    {
        $prefix = 'BILL';
        $year = date('Y');
        $month = date('m');
        
        $lastBill = self::where('bill_number', 'like', "{$prefix}-{$year}{$month}-%")
            ->orderBy('bill_number', 'desc')
            ->first();

        if ($lastBill) {
            $lastNumber = (int) substr($lastBill->bill_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('%s-%s%s-%04d', $prefix, $year, $month, $newNumber);
    }

    /**
     * Get the client that owns the bill payment.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_identifier', 'client_identifier');
    }

    /**
     * Get the biller for this bill payment.
     */
    public function biller(): BelongsTo
    {
        return $this->belongsTo(Biller::class);
    }

    /**
     * Check if the bill is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_PENDING && $this->due_date < now();
    }

    /**
     * Check if the bill is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Check if the bill is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Get the formatted amount.
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2);
    }

    /**
     * Get the formatted due date.
     */
    public function getFormattedDueDateAttribute(): string
    {
        return $this->due_date ? $this->due_date->format('M d, Y') : 'N/A';
    }

    /**
     * Get the formatted payment date.
     */
    public function getFormattedPaymentDateAttribute(): string
    {
        return $this->payment_date ? $this->payment_date->format('M d, Y') : 'N/A';
    }

    /**
     * Get the status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PAID => 'green',
            self::STATUS_OVERDUE => 'red',
            self::STATUS_PENDING => 'yellow',
            self::STATUS_CANCELLED => 'gray',
            self::STATUS_PARTIAL => 'blue',
            default => 'gray',
        };
    }

    /**
     * Get the priority badge color.
     */
    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            self::PRIORITY_URGENT => 'red',
            self::PRIORITY_HIGH => 'orange',
            self::PRIORITY_MEDIUM => 'yellow',
            self::PRIORITY_LOW => 'green',
            default => 'gray',
        };
    }

    /**
     * Scope to get overdue bills.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where('due_date', '<', now());
    }

    /**
     * Scope to get upcoming bills.
     */
    public function scopeUpcoming($query, $days = 7)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where('due_date', '>=', now())
            ->where('due_date', '<=', now()->addDays($days));
    }

    /**
     * Scope to get paid bills.
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Scope to get pending bills.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get bills by category.
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to get bills by priority.
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }
}
