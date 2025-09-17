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
        Schema::table('patient_payments', function (Blueprint $table) {
            $table->foreignId('clinic_payment_account_id')->nullable()->constrained('clinic_payment_accounts')->onDelete('set null');
            $table->enum('payment_type', ['individual', 'account'])->default('individual');
            $table->string('account_payment_reference')->nullable(); // Reference for account-based payments
            $table->json('covered_patients')->nullable(); // JSON array of patient IDs covered by this payment
            
            // Index for performance
            $table->index(['clinic_payment_account_id', 'payment_type']);
            $table->index(['account_payment_reference']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patient_payments', function (Blueprint $table) {
            $table->dropForeign(['clinic_payment_account_id']);
            $table->dropColumn(['clinic_payment_account_id', 'payment_type', 'account_payment_reference', 'covered_patients']);
        });
    }
};
