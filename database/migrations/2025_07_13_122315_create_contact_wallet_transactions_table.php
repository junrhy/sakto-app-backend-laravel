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
        Schema::create('contact_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_wallet_id')->constrained('contact_wallets')->onDelete('cascade');
            $table->foreignId('contact_id')->constrained('contacts')->onDelete('cascade');
            $table->string('client_identifier');
            $table->enum('type', ['credit', 'debit']);
            $table->decimal('amount', 10, 2);
            $table->text('description')->nullable();
            $table->string('reference')->nullable();
            $table->decimal('balance_after', 10, 2);
            $table->timestamp('transaction_date')->useCurrent();
            $table->timestamps();

            $table->index(['contact_id', 'transaction_date'], 'contact_transactions_date_idx');
            $table->index(['client_identifier', 'type'], 'contact_transactions_client_type_idx');
            $table->index(['contact_wallet_id', 'transaction_date'], 'wallet_transactions_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_wallet_transactions');
    }
};
