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
        Schema::create('clinic_inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_inventory_item_id')->constrained()->onDelete('cascade');
            $table->enum('transaction_type', ['in', 'out', 'adjustment', 'transfer']);
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->string('reference_number')->nullable(); // invoice, receipt, etc.
            $table->string('performed_by')->nullable(); // user who performed the transaction
            $table->date('transaction_date');
            $table->string('client_identifier');
            $table->timestamps();
            
            $table->index(['client_identifier', 'transaction_type'], 'clinic_inv_trans_client_type_idx');
            $table->index(['client_identifier', 'transaction_date'], 'clinic_inv_trans_client_date_idx');
            $table->index('reference_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinic_inventory_transactions');
    }
};
