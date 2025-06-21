<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'price',
        'stock_quantity',
        'weight',
        'dimensions',
        'thumbnail_url',
        'attributes',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'weight' => 'decimal:2',
        'stock_quantity' => 'integer',
        'attributes' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the product that owns the variant
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the effective price (variant price or product price)
     */
    public function getEffectivePriceAttribute()
    {
        return $this->price ?? $this->product->price;
    }

    /**
     * Get the effective weight (variant weight or product weight)
     */
    public function getEffectiveWeightAttribute()
    {
        return $this->weight ?? $this->product->weight;
    }

    /**
     * Get the effective dimensions (variant dimensions or product dimensions)
     */
    public function getEffectiveDimensionsAttribute()
    {
        return $this->dimensions ?? $this->product->dimensions;
    }

    /**
     * Check if variant is in stock
     */
    public function isInStock(): bool
    {
        return $this->stock_quantity > 0;
    }

    /**
     * Check if variant is low in stock
     */
    public function isLowStock(int $threshold = 10): bool
    {
        return $this->stock_quantity > 0 && $this->stock_quantity <= $threshold;
    }

    /**
     * Get stock status text
     */
    public function getStockStatusAttribute(): string
    {
        if ($this->stock_quantity === 0) {
            return 'Out of Stock';
        }
        
        if ($this->stock_quantity <= 10) {
            return "Low Stock ({$this->stock_quantity})";
        }
        
        return "In Stock ({$this->stock_quantity})";
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute(): string
    {
        return '₱' . number_format($this->effective_price, 2);
    }

    /**
     * Get attribute display string
     */
    public function getAttributeDisplayAttribute(): string
    {
        if (empty($this->attributes)) {
            return 'Default';
        }

        $parts = [];
        foreach ($this->attributes as $key => $value) {
            $parts[] = ucfirst($key) . ': ' . ucfirst($value);
        }

        return implode(', ', $parts);
    }

    /**
     * Get active variants for a product
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get variants in stock
     */
    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    /**
     * Get variants low in stock
     */
    public function scopeLowStock($query, $threshold = 10)
    {
        return $query->where('stock_quantity', '<=', $threshold)
                    ->where('stock_quantity', '>', 0);
    }

    /**
     * Get variants out of stock
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('stock_quantity', '<=', 0);
    }
} 