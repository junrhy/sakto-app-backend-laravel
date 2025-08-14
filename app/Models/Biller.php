<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Biller extends Model
{
    protected $fillable = [
        'name',
        'description',
        'contact_person',
        'email',
        'phone',
        'website',
        'address',
        'account_number',
        'category',
        'is_active',
        'client_identifier',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function billPayments(): HasMany
    {
        return $this->hasMany(BillPayment::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(BillerFavorite::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_identifier', 'client_identifier');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }
}
