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
        Schema::create('food_delivery_menu_categories', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('display_order')->default(0);
            $table->timestamps();
            
            // Indexes
            $table->index('client_identifier');
            $table->index('display_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_delivery_menu_categories');
    }
};
