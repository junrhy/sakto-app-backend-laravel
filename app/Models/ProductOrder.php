<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ProductOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'client_identifier',
        'contact_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'shipping_address',
        'billing_address',
        'order_items',
        'subtotal',
        'tax_amount',
        'shipping_fee',
        'service_fee',
        'discount_amount',
        'total_amount',
        'order_status',
        'payment_status',
        'payment_method',
        'payment_reference',
        'notes',
        'paid_at',
        'shipped_at',
        'delivered_at',
    ];

    protected $casts = [
        'order_items' => 'array',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'service_fee' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    /**
     * Get the contact that placed this order
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get orders for a specific client
     */
    public function scopeForClient($query, $clientIdentifier)
    {
        return $query->where('client_identifier', $clientIdentifier);
    }

    /**
     * Get orders by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('order_status', $status);
    }

    /**
     * Get orders by payment status
     */
    public function scopeByPaymentStatus($query, $paymentStatus)
    {
        return $query->where('payment_status', $paymentStatus);
    }

    /**
     * Get recent orders (within specified days)
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Generate a unique order number
     */
    public static function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(6));
        } while (static::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    /**
     * Mark order as paid
     */
    public function markAsPaid(): void
    {
        $this->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    /**
     * Mark order as shipped
     */
    public function markAsShipped(): void
    {
        $this->updateOrderStatus('shipped');
        $this->update([
            'shipped_at' => now(),
        ]);
    }

    /**
     * Mark order as delivered
     */
    public function markAsDelivered(): void
    {
        $this->updateOrderStatus('delivered');
        $this->update([
            'delivered_at' => now(),
        ]);
    }

    /**
     * Cancel order
     */
    public function cancel(): void
    {
        $this->updateOrderStatus('cancelled');
    }

    /**
     * Get order status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->order_status) {
            'pending' => 'yellow',
            'confirmed' => 'blue',
            'processing' => 'purple',
            'shipped' => 'indigo',
            'delivered' => 'green',
            'cancelled' => 'red',
            'refunded' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get payment status badge color
     */
    public function getPaymentStatusColorAttribute(): string
    {
        return match($this->payment_status) {
            'pending' => 'yellow',
            'paid' => 'green',
            'failed' => 'red',
            'refunded' => 'gray',
            'partially_refunded' => 'orange',
            default => 'gray',
        };
    }

    /**
     * Get formatted total amount
     */
    public function getFormattedTotalAttribute(): string
    {
        return '₱' . number_format($this->total_amount, 2);
    }

    /**
     * Get formatted subtotal
     */
    public function getFormattedSubtotalAttribute(): string
    {
        return '₱' . number_format($this->subtotal, 2);
    }

    /**
     * Get order items count
     */
    public function getItemsCountAttribute(): int
    {
        return collect($this->order_items)->sum('quantity');
    }

    /**
     * Check if order is paid
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Check if order is delivered
     */
    public function isDelivered(): bool
    {
        return $this->order_status === 'delivered';
    }

    /**
     * Check if order is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->order_status === 'cancelled';
    }

    /**
     * Check if order can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->order_status, ['pending', 'confirmed', 'processing']);
    }

    /**
     * Get order items with product details
     */
    public function getOrderItemsWithProductsAttribute()
    {
        $items = collect($this->order_items);
        
        return $items->map(function ($item) {
            $product = Product::find($item['product_id']);
            return [
                'product' => $product,
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'subtotal' => $item['quantity'] * $item['price'],
            ];
        });
    }

    /**
     * Adjust stock quantities based on order status
     */
    public function adjustStockForOrderStatus(string $newStatus, string $previousStatus = null): void
    {
        // Only process physical products
        $physicalItems = collect($this->order_items)->filter(function ($item) {
            $product = Product::find($item['product_id']);
            return $product && $product->type === 'physical';
        });

        if ($physicalItems->isEmpty()) {
            return;
        }

        // Handle stock adjustments based on status transitions
        switch ($newStatus) {
            case 'confirmed':
                // Reserve stock when order is confirmed
                $this->reserveStock($physicalItems);
                break;
                
            case 'processing':
                // Stock is already reserved, no additional adjustment needed
                break;
                
            case 'shipped':
                // Stock remains reserved during shipping
                break;
                
            case 'delivered':
                // Stock is already consumed during reservation, no additional action needed
                break;
                
            case 'cancelled':
                // Restore stock when cancelled
                $this->restoreStock($physicalItems);
                break;
                
            case 'refunded':
                // Restore stock when refunded
                $this->restoreStock($physicalItems);
                break;
                
            case 'pending':
                // If moving back to pending from a confirmed state, restore stock
                if (in_array($previousStatus, ['confirmed', 'processing', 'shipped'])) {
                    $this->restoreStock($physicalItems);
                }
                // If initial pending state, no stock adjustment needed
                break;
        }
    }

    /**
     * Adjust stock quantities based on order item status
     */
    public function adjustStockForItemStatus(int $productId, string $newStatus, string $previousStatus = null): void
    {
        $product = Product::find($productId);
        if (!$product || $product->type !== 'physical') {
            return;
        }

        $item = collect($this->order_items)->firstWhere('product_id', $productId);
        if (!$item) {
            return;
        }

        $quantity = $item['quantity'];

        // Handle stock adjustments based on item status transitions
        switch ($newStatus) {
            case 'confirmed':
                // Reserve stock when item is confirmed
                if ($previousStatus !== 'confirmed') {
                    $this->reserveStockForItem($product, $quantity);
                }
                break;
                
            case 'processing':
                // Stock is already reserved, no additional adjustment needed
                break;
                
            case 'shipped':
                // Stock remains reserved during shipping
                break;
                
            case 'delivered':
                // Stock is already consumed during reservation, no additional action needed
                break;
                
            case 'cancelled':
            case 'out_of_stock':
                // Restore stock when item is cancelled or out of stock
                if (!in_array($previousStatus, ['cancelled', 'out_of_stock'])) {
                    $this->restoreStockForItem($product, $quantity);
                }
                break;
                
            case 'pending':
                // If moving back to pending from a confirmed state, restore stock
                if (in_array($previousStatus, ['confirmed', 'processing', 'shipped'])) {
                    $this->restoreStockForItem($product, $quantity);
                }
                break;
        }
    }

    /**
     * Reserve stock for order items
     */
    private function reserveStock($items): void
    {
        foreach ($items as $item) {
            $product = Product::find($item['product_id']);
            if ($product && $product->stock_quantity >= $item['quantity']) {
                $product->decrement('stock_quantity', $item['quantity']);
            }
        }
    }

    /**
     * Consume stock for order items (final consumption)
     */
    private function consumeStock($items): void
    {
        // For delivered orders, stock is already consumed during reservation
        // This method is for cases where we need to ensure stock is consumed
        foreach ($items as $item) {
            $product = Product::find($item['product_id']);
            if ($product) {
                // Stock was already decremented during reservation
                // No additional action needed for delivery
            }
        }
    }

    /**
     * Restore stock for order items
     */
    private function restoreStock($items): void
    {
        foreach ($items as $item) {
            $product = Product::find($item['product_id']);
            if ($product) {
                $product->increment('stock_quantity', $item['quantity']);
            }
        }
    }

    /**
     * Reserve stock for a specific item
     */
    private function reserveStockForItem(Product $product, int $quantity): void
    {
        if ($product->stock_quantity >= $quantity) {
            $product->decrement('stock_quantity', $quantity);
        }
    }

    /**
     * Consume stock for a specific item
     */
    private function consumeStockForItem(Product $product, int $quantity): void
    {
        // Stock was already decremented during reservation
        // No additional action needed for delivery
    }

    /**
     * Restore stock for a specific item
     */
    private function restoreStockForItem(Product $product, int $quantity): void
    {
        $product->increment('stock_quantity', $quantity);
    }

    /**
     * Get the previous order status before update
     */
    public function getPreviousStatus(): ?string
    {
        return $this->getOriginal('order_status');
    }

    /**
     * Get the previous item status for a specific product
     */
    public function getPreviousItemStatus(int $productId): ?string
    {
        $originalItems = $this->getOriginal('order_items') ?? [];
        $item = collect($originalItems)->firstWhere('product_id', $productId);
        return $item['status'] ?? null;
    }

    /**
     * Update order status with stock adjustment
     */
    public function updateOrderStatus(string $newStatus): void
    {
        $previousStatus = $this->getPreviousStatus();
        
        $this->update(['order_status' => $newStatus]);
        
        // Adjust stock based on status change
        $this->adjustStockForOrderStatus($newStatus, $previousStatus);
    }

    /**
     * Update item status with stock adjustment
     */
    public function updateItemStatus(int $productId, string $newStatus): void
    {
        $previousStatus = $this->getPreviousItemStatus($productId);
        
        // Update the item status in order_items
        $updatedItems = collect($this->order_items)->map(function ($item) use ($productId, $newStatus) {
            if ($item['product_id'] == $productId) {
                $item['status'] = $newStatus;
            }
            return $item;
        })->toArray();
        
        $this->update(['order_items' => $updatedItems]);
        
        // Adjust stock based on item status change
        $this->adjustStockForItemStatus($productId, $newStatus, $previousStatus);
    }

    /**
     * Check if stock is available for all items
     */
    public function hasAvailableStock(): bool
    {
        foreach ($this->order_items as $item) {
            $product = Product::find($item['product_id']);
            if ($product && $product->type === 'physical') {
                if ($product->stock_quantity < $item['quantity']) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Get stock availability for each item
     */
    public function getStockAvailability(): array
    {
        $availability = [];
        
        foreach ($this->order_items as $item) {
            $product = Product::find($item['product_id']);
            if ($product && $product->type === 'physical') {
                $availability[] = [
                    'product_id' => $item['product_id'],
                    'product_name' => $product->name,
                    'requested_quantity' => $item['quantity'],
                    'available_quantity' => $product->stock_quantity,
                    'is_available' => $product->stock_quantity >= $item['quantity'],
                    'item_status' => $item['status'] ?? 'pending',
                ];
            } else {
                $availability[] = [
                    'product_id' => $item['product_id'],
                    'product_name' => $product ? $product->name : 'Unknown Product',
                    'requested_quantity' => $item['quantity'],
                    'available_quantity' => null,
                    'is_available' => true, // Non-physical products are always available
                    'item_status' => $item['status'] ?? 'pending',
                ];
            }
        }
        
        return $availability;
    }

    /**
     * Get current stock status summary for the order
     */
    public function getStockStatusSummary(): array
    {
        $physicalItems = collect($this->order_items)->filter(function ($item) {
            $product = Product::find($item['product_id']);
            return $product && $product->type === 'physical';
        });

        $confirmedItems = $physicalItems->filter(function ($item) {
            return ($item['status'] ?? 'pending') === 'confirmed';
        });

        $pendingItems = $physicalItems->filter(function ($item) {
            return ($item['status'] ?? 'pending') === 'pending';
        });

        return [
            'order_status' => $this->order_status,
            'total_physical_items' => $physicalItems->count(),
            'confirmed_items' => $confirmedItems->count(),
            'pending_items' => $pendingItems->count(),
            'stock_reserved' => $confirmedItems->count() > 0,
            'can_confirm' => $this->hasAvailableStock() && $this->order_status === 'pending',
        ];
    }
} 