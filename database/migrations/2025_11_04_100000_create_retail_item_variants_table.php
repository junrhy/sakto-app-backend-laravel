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
        if (Schema::hasTable('retail_item_variants')) {
            return;
        }

        Schema::create('retail_item_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retail_item_id')->constrained('retail_items')->onDelete('cascade');
            $table->string('client_identifier')->index();
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable();
            $table->decimal('price', 10, 2)->nullable(); // Override item price if different
            $table->integer('quantity')->default(0);
            $table->json('attributes'); // e.g., {"color": "red", "size": "L"}
            $table->string('image')->nullable(); // Variant-specific image
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index(['retail_item_id', 'is_active']);
            $table->index(['client_identifier', 'retail_item_id']);
            $table->index('sku');
            $table->index('barcode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retail_item_variants');
    }
};

