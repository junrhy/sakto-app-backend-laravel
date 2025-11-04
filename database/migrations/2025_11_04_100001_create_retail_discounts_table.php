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
        if (Schema::hasTable('retail_discounts')) {
            return;
        }

        Schema::create('retail_discounts', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['percentage', 'fixed', 'buy_x_get_y'])->default('percentage');
            $table->decimal('value', 10, 2); // Percentage or fixed amount
            $table->integer('min_quantity')->nullable(); // Minimum quantity for discount
            $table->integer('buy_quantity')->nullable(); // For buy_x_get_y: buy this many
            $table->integer('get_quantity')->nullable(); // For buy_x_get_y: get this many free
            $table->decimal('min_purchase_amount', 10, 2)->nullable(); // Minimum purchase amount
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('applicable_items')->nullable(); // Array of item IDs, null for all items
            $table->json('applicable_categories')->nullable(); // Array of category IDs
            $table->integer('usage_limit')->nullable(); // Max number of times discount can be used
            $table->integer('usage_count')->default(0); // Current usage count
            $table->timestamps();

            // Indexes
            $table->index(['client_identifier', 'is_active']);
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retail_discounts');
    }
};

