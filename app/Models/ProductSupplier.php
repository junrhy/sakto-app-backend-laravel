<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductSupplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'email',
        'phone',
        'website',
        'contact_person',
        'address',
    ];

    /**
     * Get the product that owns this supplier
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the purchase records for this supplier
     */
    public function purchaseRecords()
    {
        return $this->hasMany(ProductPurchaseRecord::class);
    }
} 