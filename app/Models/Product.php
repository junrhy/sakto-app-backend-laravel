<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'category',
        'type',
        'sku',
        'stock_quantity',
        'weight',
        'dimensions',
        'file_url',
        'thumbnail_url',
        'status',
        'tags',
        'metadata',
        'client_identifier',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'weight' => 'decimal:2',
        'stock_quantity' => 'integer',
        'tags' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the products for a specific client
     */
    public function scopeForClient($query, $clientIdentifier)
    {
        return $query->where('client_identifier', $clientIdentifier);
    }

    /**
     * Get products by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get products by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Get products by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get products that are in stock (for physical products)
     */
    public function scopeInStock($query)
    {
        return $query->where(function ($q) {
            $q->where('type', '!=', 'physical')
              ->orWhere('stock_quantity', '>', 0);
        });
    }

    /**
     * Get products that are low in stock (for physical products)
     */
    public function scopeLowStock($query, $threshold = 10)
    {
        return $query->where('type', 'physical')
                    ->where('stock_quantity', '<=', $threshold)
                    ->where('stock_quantity', '>', 0);
    }

    /**
     * Get products that are out of stock (for physical products)
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('type', 'physical')
                    ->where('stock_quantity', '<=', 0);
    }

    /**
     * Check if product is in stock
     */
    public function isInStock(): bool
    {
        if ($this->type !== 'physical') {
            return true; // Digital, service, and subscription products are always "in stock"
        }
        
        return $this->stock_quantity > 0;
    }

    /**
     * Check if product is low in stock
     */
    public function isLowStock(int $threshold = 10): bool
    {
        if ($this->type !== 'physical') {
            return false;
        }
        
        return $this->stock_quantity > 0 && $this->stock_quantity <= $threshold;
    }

    /**
     * Get stock status text
     */
    public function getStockStatusAttribute(): string
    {
        if ($this->type !== 'physical') {
            return 'Unlimited';
        }
        
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
        return '₱' . number_format($this->price, 2);
    }

    /**
     * Get file name from file_url
     */
    public function getFileNameAttribute(): ?string
    {
        if (!$this->file_url) {
            return null;
        }
        
        return basename($this->file_url);
    }

    /**
     * Get file extension from file_url
     */
    public function getFileExtensionAttribute(): ?string
    {
        if (!$this->file_url) {
            return null;
        }
        
        return pathinfo($this->file_url, PATHINFO_EXTENSION);
    }
}
