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
     * Get the variants for this product
     */
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Get the active variants for this product
     */
    public function activeVariants()
    {
        return $this->hasMany(ProductVariant::class)->active();
    }

    /**
     * Set the tags attribute, ensuring it's always an array
     */
    public function setTagsAttribute($value)
    {
        $this->attributes['tags'] = is_array($value) ? json_encode($value) : json_encode([]);
    }

    /**
     * Get the tags attribute, ensuring it's always an array
     */
    public function getTagsAttribute($value)
    {
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

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
              ->orWhere('stock_quantity', '>', 0)
              ->orWhereHas('variants', function ($variantQuery) {
                  $variantQuery->active()->where('stock_quantity', '>', 0);
              });
        });
    }

    /**
     * Get products that are low in stock (for physical products)
     */
    public function scopeLowStock($query, $threshold = 10)
    {
        return $query->where('type', 'physical')
                    ->where(function ($q) use ($threshold) {
                        $q->where(function ($subQ) use ($threshold) {
                            $subQ->where('stock_quantity', '<=', $threshold)
                                 ->where('stock_quantity', '>', 0);
                        })->orWhereHas('variants', function ($variantQuery) use ($threshold) {
                            $variantQuery->active()->lowStock($threshold);
                        });
                    });
    }

    /**
     * Get products that are out of stock (for physical products)
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('type', 'physical')
                    ->where(function ($q) {
                        $q->where('stock_quantity', '<=', 0)
                          ->whereDoesntHave('variants', function ($variantQuery) {
                              $variantQuery->active()->where('stock_quantity', '>', 0);
                          });
                    });
    }

    /**
     * Check if product is in stock
     */
    public function isInStock(): bool
    {
        if ($this->type !== 'physical') {
            return true; // Digital, service, and subscription products are always "in stock"
        }
        
        // Check main product stock
        if ($this->stock_quantity > 0) {
            return true;
        }
        
        // Check if any variants are in stock
        return $this->variants()->active()->where('stock_quantity', '>', 0)->exists();
    }

    /**
     * Check if product is low in stock
     */
    public function isLowStock(int $threshold = 10): bool
    {
        if ($this->type !== 'physical') {
            return false;
        }
        
        // Check main product stock
        if ($this->stock_quantity > 0 && $this->stock_quantity <= $threshold) {
            return true;
        }
        
        // Check if any variants are low in stock
        return $this->variants()->active()->lowStock($threshold)->exists();
    }

    /**
     * Get total stock quantity (including variants)
     */
    public function getTotalStockQuantityAttribute(): int
    {
        if ($this->type !== 'physical') {
            return 0; // Not applicable for non-physical products
        }
        
        $mainStock = $this->stock_quantity ?? 0;
        $variantStock = $this->variants()->active()->sum('stock_quantity');
        
        return $mainStock + $variantStock;
    }

    /**
     * Get stock status text
     */
    public function getStockStatusAttribute(): string
    {
        if ($this->type !== 'physical') {
            return 'Unlimited';
        }
        
        $totalStock = $this->total_stock_quantity;
        
        if ($totalStock === 0) {
            return 'Out of Stock';
        }
        
        if ($totalStock <= 10) {
            return "Low Stock ({$totalStock})";
        }
        
        return "In Stock ({$totalStock})";
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

    /**
     * Check if product has variants
     */
    public function hasVariants(): bool
    {
        return $this->type === 'physical' && $this->variants()->active()->exists();
    }

    /**
     * Get available attribute options for variants
     */
    public function getAvailableAttributesAttribute(): array
    {
        if (!$this->hasVariants()) {
            return [];
        }

        $attributes = [];
        $variants = $this->variants()->active()->get();

        foreach ($variants as $variant) {
            if (!empty($variant->attributes)) {
                foreach ($variant->attributes as $key => $value) {
                    if (!isset($attributes[$key])) {
                        $attributes[$key] = [];
                    }
                    if (!in_array($value, $attributes[$key])) {
                        $attributes[$key][] = $value;
                    }
                }
            }
        }

        return $attributes;
    }
}
