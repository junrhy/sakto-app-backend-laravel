<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillerFavorite extends Model
{
    protected $fillable = [
        'biller_id',
        'contact_id',
        'client_identifier',
    ];

    // Relationships
    public function biller(): BelongsTo
    {
        return $this->belongsTo(Biller::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_identifier', 'client_identifier');
    }
}
