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
        Schema::create('clinic_inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['medicine', 'equipment', 'supply', 'other']);
            $table->string('category')->nullable();
            $table->string('sku')->unique();
            $table->string('barcode')->nullable();
            $table->decimal('unit_price', 10, 2);
            $table->integer('current_stock')->default(0);
            $table->integer('minimum_stock')->default(0);
            $table->integer('maximum_stock')->nullable();
            $table->string('unit_of_measure')->default('pieces'); // pieces, bottles, boxes, etc.
            $table->date('expiry_date')->nullable();
            $table->string('supplier')->nullable();
            $table->string('location')->nullable(); // storage location
            $table->boolean('is_active')->default(true);
            $table->string('client_identifier');
            $table->timestamps();
            
            $table->index(['client_identifier', 'type']);
            $table->index(['client_identifier', 'category']);
            $table->index('expiry_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinic_inventory_items');
    }
};
