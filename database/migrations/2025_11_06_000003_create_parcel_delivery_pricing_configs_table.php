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
        Schema::create('parcel_delivery_pricing_configs', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier');
            $table->string('config_name')->default('Default Pricing');
            
            // Base rates for each delivery type
            $table->json('base_rates')->nullable(); // {"express": 150, "standard": 100, "economy": 80}
            
            // Distance rates (per km)
            $table->decimal('distance_rate_per_km', 8, 2)->default(5.00);
            
            // Weight rates (tiered pricing per kg)
            $table->json('weight_rates')->nullable(); // {"0-5": 2.00, "5-10": 3.00, "10-20": 4.00, "20+": 5.00}
            
            // Size rates (volume-based pricing per cubic cm)
            $table->decimal('size_rate_per_cubic_cm', 8, 4)->default(0.01);
            
            // Delivery type multipliers
            $table->json('delivery_type_multipliers')->nullable(); // {"express": 1.5, "standard": 1.0, "economy": 0.8}
            
            // Surcharges (as percentages)
            $table->json('surcharges')->nullable(); // {"fuel": 0.10, "peak_hour": 0.15, "weekend": 0.20, "holiday": 0.30, "urgent": 0.25}
            
            // Peak hours configuration
            $table->json('peak_hours')->nullable(); // [["07:00", "09:00"], ["17:00", "20:00"]]
            
            // Holidays configuration
            $table->json('holidays')->nullable(); // ["2024-01-01", "2024-12-25", ...]
            
            // Insurance rate (percentage of package value)
            $table->decimal('insurance_rate', 5, 4)->default(0.01); // 1% of package value
            
            // Minimum charge
            $table->decimal('minimum_charge', 10, 2)->default(50.00);
            
            // Currency and formatting
            $table->string('currency')->default('PHP');
            $table->string('currency_symbol')->default('₱');
            $table->integer('decimal_places')->default(2);
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->string('version')->default('v1.0');
            $table->text('description')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['client_identifier', 'is_active'], 'pd_pricing_configs_client_active_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parcel_delivery_pricing_configs');
    }
};

