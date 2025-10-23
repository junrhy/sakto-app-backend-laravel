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
        Schema::create('fnb_online_stores', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('domain')->unique();
            $table->boolean('is_active')->default(true);
            $table->json('menu_items')->nullable(); // Array of menu item IDs
            $table->json('settings')->nullable(); // Store-specific settings
            $table->string('verification_required')->default('auto'); // auto, manual, none
            $table->boolean('payment_negotiation_enabled')->default(false);
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['client_identifier', 'is_active']);
            $table->index(['client_identifier', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fnb_online_stores');
    }
};