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
        Schema::dropIfExists('fnb_customer_orders');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('fnb_customer_orders', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier')->index();
            $table->unsignedBigInteger('table_id');
            $table->string('table_name');
            $table->string('customer_name')->nullable();
            $table->json('items');
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'preparing', 'served', 'cancelled'])->default('pending');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->timestamps();

            $table->index(['client_identifier', 'status']);
            $table->index(['client_identifier', 'table_id']);
        });
    }
};
