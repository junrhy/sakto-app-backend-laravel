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
        Schema::create('queue_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier')->index();
            $table->foreignId('queue_type_id')->constrained('queue_types')->onDelete('cascade');
            $table->string('queue_number'); // e.g., Q001, Q002
            $table->string('customer_name')->nullable();
            $table->string('customer_contact')->nullable();
            $table->enum('status', ['waiting', 'called', 'serving', 'completed', 'cancelled'])->default('waiting');
            $table->integer('priority')->default(0); // For priority queue
            $table->timestamp('called_at')->nullable();
            $table->timestamp('serving_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['client_identifier', 'status']);
            $table->index(['queue_type_id', 'status']);
            $table->index(['client_identifier', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue_numbers');
    }
};
