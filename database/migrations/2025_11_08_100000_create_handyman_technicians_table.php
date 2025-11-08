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
        Schema::create('handyman_technicians', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier')->index();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('specialty')->nullable();
            $table->json('skills')->nullable();
            $table->string('status')->default('available');
            $table->string('location')->nullable();
            $table->unsignedInteger('current_load')->default(0);
            $table->timestamps();

            $table->index(['client_identifier', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('handyman_technicians');
    }
};

