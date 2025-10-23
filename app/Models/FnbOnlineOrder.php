<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FnbOnlineOrder extends Model
{
    protected $fillable = [
        'client_identifier',
        'online_store_id',
        'order_number',
        'customer_name',
        'customer_email',
        'customer_phone',
        'delivery_address',
        'items',
        'subtotal',
        'delivery_fee',
        'tax_amount',
        'total_amount',
        'status',
        'verification_status',
        'verification_notes',
        'payment_negotiation_enabled',
        'negotiated_amount',
        'payment_notes',
        'payment_status',
        'payment_method',
        'verified_at',
        'preparing_at',
        'ready_at',
        'delivered_at',
    ];

    protected $casts = [
        'items' => 'array',
        'subtotal' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'negotiated_amount' => 'decimal:2',
        'payment_negotiation_enabled' => 'boolean',
        'verified_at' => 'datetime',
        'preparing_at' => 'datetime',
        'ready_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    /**
     * Get the online store that owns the order
     */
    public function onlineStore(): BelongsTo
    {
        return $this->belongsTo(FnbOnlineStore::class, 'online_store_id');
    }

    /**
     * Scope for client
     */
    public function scopeForClient($query, $clientIdentifier)
    {
        return $query->where('client_identifier', $clientIdentifier);
    }

    /**
     * Scope for pending verification
     */
    public function scopePendingVerification($query)
    {
        return $query->where('verification_status', 'pending');
    }

    /**
     * Scope for verified orders
     */
    public function scopeVerified($query)
    {
        return $query->where('verification_status', 'verified');
    }

    /**
     * Scope for pending payment negotiation
     */
    public function scopePendingPaymentNegotiation($query)
    {
        return $query->where('payment_negotiation_enabled', true)
                    ->where('payment_status', 'pending');
    }

    /**
     * Generate unique order number
     */
    public static function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ON' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (self::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    /**
     * Check if order can be verified
     */
    public function canBeVerified(): bool
    {
        return $this->verification_status === 'pending';
    }

    /**
     * Check if order can be prepared
     */
    public function canBePrepared(): bool
    {
        return $this->verification_status === 'verified' && 
               $this->status === 'pending' &&
               ($this->payment_status === 'paid' || !$this->payment_negotiation_enabled);
    }

    /**
     * Check if order can be delivered
     */
    public function canBeDelivered(): bool
    {
        return $this->status === 'ready';
    }

    /**
     * Get order status badge color
     */
    public function getStatusColor(): string
    {
        return match($this->status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'verified' => 'bg-blue-100 text-blue-800',
            'preparing' => 'bg-orange-100 text-orange-800',
            'ready' => 'bg-green-100 text-green-800',
            'delivered' => 'bg-gray-100 text-gray-800',
            'cancelled' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get verification status badge color
     */
    public function getVerificationStatusColor(): string
    {
        return match($this->verification_status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'verified' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get payment status badge color
     */
    public function getPaymentStatusColor(): string
    {
        return match($this->payment_status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'negotiated' => 'bg-blue-100 text-blue-800',
            'paid' => 'bg-green-100 text-green-800',
            'failed' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}