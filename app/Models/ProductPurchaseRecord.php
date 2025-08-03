<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPurchaseRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'product_supplier_id',
        'price',
        'currency',
        'date',
        'order_number',
        'notes',
        'reorder_point',
        'reorder_quantity',
        'lead_time_days',
        'payment_terms',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'date' => 'date',
        'reorder_point' => 'integer',
        'reorder_quantity' => 'integer',
        'lead_time_days' => 'integer',
    ];

    /**
     * Get the product that owns this purchase record
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the supplier for this purchase record
     */
    public function supplier()
    {
        return $this->belongsTo(ProductSupplier::class, 'product_supplier_id');
    }
} 