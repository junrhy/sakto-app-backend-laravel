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
        Schema::create('product_purchase_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('product_supplier_id')->nullable()->constrained('product_suppliers')->onDelete('set null');
            $table->decimal('price', 10, 2);
            $table->string('currency', 10)->default('USD');
            $table->date('date')->nullable();
            $table->string('order_number')->nullable();
            $table->text('notes')->nullable();
            $table->integer('reorder_point')->nullable();
            $table->integer('reorder_quantity')->nullable();
            $table->integer('lead_time_days')->nullable();
            $table->string('payment_terms')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['product_id']);
            $table->index(['product_supplier_id']);
            $table->index('date');
            $table->index('order_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_purchase_records');
    }
}; 