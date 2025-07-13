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
        Schema::create('contact_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained('contacts')->onDelete('cascade');
            $table->string('client_identifier');
            $table->decimal('balance', 10, 2)->default(0);
            $table->string('currency', 3)->default('PHP');
            $table->enum('status', ['active', 'suspended', 'closed'])->default('active');
            $table->timestamp('last_transaction_date')->nullable();
            $table->timestamps();

            $table->unique(['contact_id', 'client_identifier']);
            $table->index(['client_identifier', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_wallets');
    }
};
