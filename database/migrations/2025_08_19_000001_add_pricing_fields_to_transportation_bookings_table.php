<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transportation_bookings', function (Blueprint $table) {
            // Distance and route information
            $table->decimal('distance_km', 10, 2)->nullable()->after('cargo_unit');
            $table->string('route_type')->default('local')->after('distance_km'); // local, provincial, intercity
            
            // Pricing breakdown
            $table->decimal('base_rate', 10, 2)->nullable()->after('estimated_cost');
            $table->decimal('distance_rate', 10, 2)->nullable()->after('base_rate');
            $table->decimal('weight_rate', 10, 2)->nullable()->after('distance_rate');
            $table->decimal('special_handling_rate', 10, 2)->nullable()->after('weight_rate');
            $table->decimal('fuel_surcharge', 10, 2)->nullable()->after('special_handling_rate');
            $table->decimal('peak_hour_surcharge', 10, 2)->nullable()->after('fuel_surcharge');
            $table->decimal('weekend_surcharge', 10, 2)->nullable()->after('peak_hour_surcharge');
            $table->decimal('holiday_surcharge', 10, 2)->nullable()->after('weekend_surcharge');
            $table->decimal('driver_overtime_rate', 10, 2)->nullable()->after('holiday_surcharge');
            
            // Additional service flags
            $table->boolean('requires_refrigeration')->default(false)->after('driver_overtime_rate');
            $table->boolean('requires_special_equipment')->default(false)->after('requires_refrigeration');
            $table->boolean('requires_escort')->default(false)->after('requires_special_equipment');
            $table->boolean('is_urgent_delivery')->default(false)->after('requires_escort');
            
            // Time-based pricing
            $table->time('pickup_hour')->nullable()->after('is_urgent_delivery');
            $table->time('delivery_hour')->nullable()->after('pickup_hour');
            
            // Insurance and additional costs
            $table->decimal('insurance_cost', 10, 2)->nullable()->after('delivery_hour');
            $table->decimal('toll_fees', 10, 2)->nullable()->after('insurance_cost');
            $table->decimal('parking_fees', 10, 2)->nullable()->after('toll_fees');
            
            // Pricing metadata
            $table->json('pricing_breakdown')->nullable()->after('parking_fees');
            $table->string('pricing_version')->default('v1.0')->after('pricing_breakdown');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transportation_bookings', function (Blueprint $table) {
            $table->dropColumn([
                'distance_km',
                'route_type',
                'base_rate',
                'distance_rate',
                'weight_rate',
                'special_handling_rate',
                'fuel_surcharge',
                'peak_hour_surcharge',
                'weekend_surcharge',
                'holiday_surcharge',
                'driver_overtime_rate',
                'requires_refrigeration',
                'requires_special_equipment',
                'requires_escort',
                'is_urgent_delivery',
                'pickup_hour',
                'delivery_hour',
                'insurance_cost',
                'toll_fees',
                'parking_fees',
                'pricing_breakdown',
                'pricing_version'
            ]);
        });
    }
};
