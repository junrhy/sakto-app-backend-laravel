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
        Schema::create('fnb_reservations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('date');
            $table->string('time');
            $table->string('guests');
            $table->string('table_id');
            $table->string('notes')->nullable();
            $table->string('contact')->nullable();
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
        Schema::dropIfExists('fnb_reservations');
    }
};
