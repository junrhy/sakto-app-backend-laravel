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
        if (Schema::hasTable('retail_stock_transactions')) {
            return;
        }
        
        Schema::create('retail_stock_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier')->index();
            $table->foreignId('retail_item_id')->constrained('retail_items')->onDelete('cascade');
            $table->enum('transaction_type', ['add', 'remove', 'adjustment'])->default('adjustment');
            $table->integer('quantity')->default(0);
            $table->integer('previous_quantity')->default(0);
            $table->integer('new_quantity')->default(0);
            $table->text('reason')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('performed_by')->nullable();
            $table->timestamp('transaction_date')->useCurrent();
            $table->timestamps();
            
            $table->index(['client_identifier', 'retail_item_id'], 'rst_client_item_idx');
            $table->index(['client_identifier', 'transaction_date'], 'rst_client_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retail_stock_transactions');
    }
};
