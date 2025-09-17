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
        Schema::table('patient_bills', function (Blueprint $table) {
            $table->foreignId('clinic_payment_account_id')->nullable()->constrained('clinic_payment_accounts')->onDelete('set null');
            $table->enum('billing_type', ['individual', 'account'])->default('individual');
            $table->string('account_bill_reference')->nullable(); // Reference for account-based bills
            
            // Index for performance
            $table->index(['clinic_payment_account_id', 'billing_type']);
            $table->index(['account_bill_reference']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patient_bills', function (Blueprint $table) {
            $table->dropForeign(['clinic_payment_account_id']);
            $table->dropColumn(['clinic_payment_account_id', 'billing_type', 'account_bill_reference']);
        });
    }
};
