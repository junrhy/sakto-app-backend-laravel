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
        Schema::create('handyman_inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier')->index();
            $table->foreignId('inventory_item_id')->constrained('handyman_inventory_items')->onDelete('cascade');
            $table->foreignId('technician_id')->nullable()->constrained('handyman_technicians')->nullOnDelete();
            $table->foreignId('work_order_id')->nullable()->constrained('handyman_work_orders')->nullOnDelete();
            $table->string('transaction_type'); // check_out, check_in, consume, adjust
            $table->integer('quantity')->default(0);
            $table->json('details')->nullable();
            $table->dateTime('transaction_at')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamps();

            $table->index(['client_identifier', 'transaction_type'], 'handyman_inventory_trans_type_idx');
            $table->index(['client_identifier', 'transaction_at'], 'handyman_inventory_trans_time_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('handyman_inventory_transactions');
    }
};

