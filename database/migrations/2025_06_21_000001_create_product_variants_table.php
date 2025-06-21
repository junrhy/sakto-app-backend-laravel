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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('sku')->nullable();
            $table->decimal('price', 10, 2)->nullable(); // Override product price if different
            $table->integer('stock_quantity')->default(0);
            $table->decimal('weight', 8, 2)->nullable(); // Override product weight if different
            $table->string('dimensions')->nullable(); // Override product dimensions if different
            $table->string('thumbnail_url')->nullable(); // Variant-specific image
            $table->json('attributes'); // e.g., {"color": "red", "size": "L"}
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index(['product_id', 'is_active']);
            $table->index('sku');
            $table->index('stock_quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
}; 