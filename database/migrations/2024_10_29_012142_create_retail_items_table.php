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
        Schema::create('retail_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku');
            $table->json('images')->nullable();
            $table->integer('quantity');
            $table->string('unit')->nullable();
            $table->decimal('price');
            $table->foreignId('category_id')->constrained('retail_categories');
            $table->string('barcode')->nullable();
            $table->string('client_identifier')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retail_items');
    }
};
