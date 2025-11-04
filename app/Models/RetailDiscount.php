<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class RetailDiscount extends Model
{
    protected $fillable = [
        'client_identifier',
        'name',
        'description',
        'type',
        'value',
        'min_quantity',
        'buy_quantity',
        'get_quantity',
        'min_purchase_amount',
        'start_date',
        'end_date',
        'is_active',
        'applicable_items',
        'applicable_categories',
        'usage_limit',
        'usage_count',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_quantity' => 'integer',
        'buy_quantity' => 'integer',
        'get_quantity' => 'integer',
        'min_purchase_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'applicable_items' => 'array',
        'applicable_categories' => 'array',
        'usage_limit' => 'integer',
        'usage_count' => 'integer',
    ];

    public function scopeForClient($query, $clientIdentifier)
    {
        return $query->where('client_identifier', $clientIdentifier);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function($q) {
                $q->whereNull('start_date')
                  ->orWhere('start_date', '<=', Carbon::now());
            })
            ->where(function($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', Carbon::now());
            });
    }

    public function scopeValid($query)
    {
        return $query->active()
            ->where(function($q) {
                $q->whereNull('usage_limit')
                  ->orWhereColumn('usage_count', '<', 'usage_limit');
            });
    }

    public function isApplicableToItem($itemId, $categoryId = null, $quantity = 1, $purchaseAmount = 0)
    {
        // Check if discount is active and valid
        if (!$this->is_active) {
            return false;
        }

        // Check date range
        $now = Carbon::now();
        if ($this->start_date && $now->lt($this->start_date)) {
            return false;
        }
        if ($this->end_date && $now->gt($this->end_date)) {
            return false;
        }

        // Check usage limit
        if ($this->usage_limit && $this->usage_count >= $this->usage_limit) {
            return false;
        }

        // Check minimum purchase amount
        if ($this->min_purchase_amount && $purchaseAmount < $this->min_purchase_amount) {
            return false;
        }

        // Check minimum quantity
        if ($this->min_quantity && $quantity < $this->min_quantity) {
            return false;
        }

        // Check applicable items
        if ($this->applicable_items !== null && !in_array($itemId, $this->applicable_items)) {
            return false;
        }

        // Check applicable categories
        if ($this->applicable_categories !== null && $categoryId && !in_array($categoryId, $this->applicable_categories)) {
            return false;
        }

        return true;
    }

    public function calculateDiscount($itemPrice, $quantity = 1)
    {
        if (!$this->isApplicableToItem(0, null, $quantity, $itemPrice * $quantity)) {
            return 0;
        }

        switch ($this->type) {
            case 'percentage':
                return ($itemPrice * $quantity) * ($this->value / 100);
            case 'fixed':
                return min($this->value, $itemPrice * $quantity);
            case 'buy_x_get_y':
                if ($this->buy_quantity && $this->get_quantity) {
                    $freeItems = floor($quantity / ($this->buy_quantity + $this->get_quantity)) * $this->get_quantity;
                    return $freeItems * $itemPrice;
                }
                return 0;
            default:
                return 0;
        }
    }
}

