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
        Schema::create('fnb_tables', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('seats');
            $table->enum('status', ['available', 'occupied', 'reserved', 'joined']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fnb_tables');
    }
};
