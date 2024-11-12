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
        Schema::create('fnb_orders', function (Blueprint $table) {
            $table->id();
            $table->string('table_number');
            $table->string('client_identifier');
            $table->string('status')->default('active');
            $table->string('item')->nullable();
            $table->integer('quantity')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('total', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fnb_orders');
    }
};
