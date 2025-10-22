<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FnbKitchenOrder extends Model
{
    protected $fillable = [
        'order_number',
        'table_number',
        'customer_name',
        'customer_notes',
        'items',
        'status',
        'sent_at',
        'prepared_at',
        'ready_at',
        'completed_at',
        'client_identifier'
    ];

    protected $casts = [
        'items' => 'array',
        'sent_at' => 'datetime',
        'prepared_at' => 'datetime',
        'ready_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Generate a unique order number
     */
    public static function generateOrderNumber($clientIdentifier)
    {
        $today = now()->format('Ymd');
        $count = self::where('client_identifier', $clientIdentifier)
            ->whereDate('created_at', today())
            ->count();
        
        return 'K' . $today . '-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    }
}
