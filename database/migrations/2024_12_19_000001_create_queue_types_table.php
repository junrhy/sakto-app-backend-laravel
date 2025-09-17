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
        Schema::create('queue_types', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('prefix', 10)->default('Q');
            $table->integer('current_number')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable(); // For custom settings like working hours, etc.
            $table->timestamps();

            $table->index(['client_identifier', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue_types');
    }
};
