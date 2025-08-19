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
        Schema::create('transportation_pricing_configs', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier');
            $table->string('config_name');
            $table->string('config_type'); // base_rates, distance_rates, weight_rates, special_handling, surcharges
            
            // Base rates configuration
            $table->json('base_rates')->nullable(); // {"small": 3000, "medium": 5000, "large": 8000, "heavy": 12000}
            
            // Distance rates configuration
            $table->json('distance_rates')->nullable(); // {"local": 50, "provincial": 75, "intercity": 100}
            
            // Weight rates configuration
            $table->json('weight_rates')->nullable(); // {"light": 0, "medium": 500, "heavy": 1000, "very_heavy": 2000}
            
            // Special handling rates
            $table->json('special_handling_rates')->nullable(); // {"refrigeration": 2000, "special_equipment": 1500, "escort": 3000, "urgent": 5000}
            
            // Surcharge percentages
            $table->json('surcharges')->nullable(); // {"fuel": 0.15, "peak_hour": 0.20, "weekend": 0.25, "holiday": 0.50, "overtime": 0.30}
            
            // Peak hours configuration
            $table->json('peak_hours')->nullable(); // [["06:00", "09:00"], ["17:00", "20:00"]]
            
            // Holidays configuration
            $table->json('holidays')->nullable(); // ["2024-01-01", "2024-01-02", ...]
            
            // Additional costs configuration
            $table->json('additional_costs')->nullable(); // {"insurance_rate": 0.02, "toll_rates": {"local": 0, "provincial": 50, "intercity": 100}, "parking_fee_per_day": 200}
            
            // Overtime hours configuration
            $table->json('overtime_hours')->nullable(); // {"start": "22:00", "end": "06:00"}
            
            // Currency and formatting
            $table->string('currency')->default('PHP');
            $table->string('currency_symbol')->default('₱');
            $table->integer('decimal_places')->default(2);
            
            // Status and metadata
            $table->boolean('is_active')->default(true);
            $table->string('version')->default('v1.0');
            $table->text('description')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['client_identifier', 'config_type'], 'pricing_configs_client_type_idx');
            $table->index('is_active', 'pricing_configs_active_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transportation_pricing_configs');
    }
};
