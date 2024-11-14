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
        Schema::create('fnb_kitchen_orders', function (Blueprint $table) {
            $table->id();
            $table->string('table_number');
            $table->json('items');
            $table->string('status');
            $table->string('client_identifier');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fnb_kitchen_orders');
    }
};
