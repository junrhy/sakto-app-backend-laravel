<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update parcel_deliveries status enum
        DB::statement("ALTER TABLE parcel_deliveries MODIFY COLUMN status ENUM(
            'pending',
            'confirmed',
            'scheduled',
            'out_for_pickup',
            'picked_up',
            'at_warehouse',
            'in_transit',
            'out_for_delivery',
            'delivery_attempted',
            'delivered',
            'returned',
            'returned_to_sender',
            'on_hold',
            'failed',
            'cancelled'
        ) DEFAULT 'pending'");

        // Update parcel_delivery_trackings status enum
        DB::statement("ALTER TABLE parcel_delivery_trackings MODIFY COLUMN status ENUM(
            'pending',
            'confirmed',
            'scheduled',
            'out_for_pickup',
            'picked_up',
            'at_warehouse',
            'in_transit',
            'out_for_delivery',
            'delivery_attempted',
            'delivered',
            'returned',
            'returned_to_sender',
            'on_hold',
            'failed',
            'cancelled'
        )");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original statuses
        DB::statement("ALTER TABLE parcel_deliveries MODIFY COLUMN status ENUM(
            'pending',
            'picked_up',
            'in_transit',
            'delivered',
            'cancelled'
        ) DEFAULT 'pending'");

        DB::statement("ALTER TABLE parcel_delivery_trackings MODIFY COLUMN status ENUM(
            'pending',
            'picked_up',
            'in_transit',
            'delivered',
            'cancelled'
        )");
    }
};
