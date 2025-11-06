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
        Schema::create('parcel_delivery_external_integrations', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier');
            $table->string('service_name'); // grab, lalamove, etc
            $table->string('api_key')->nullable();
            $table->text('api_secret')->nullable();
            $table->boolean('is_active')->default(false);
            $table->json('settings')->nullable(); // Additional service-specific settings
            $table->text('description')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['client_identifier', 'service_name'], 'pd_external_client_service_idx');
            $table->index('is_active', 'pd_external_active_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parcel_delivery_external_integrations');
    }
};

