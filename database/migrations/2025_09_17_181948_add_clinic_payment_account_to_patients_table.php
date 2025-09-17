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
        Schema::table('patients', function (Blueprint $table) {
            $table->foreignId('clinic_payment_account_id')->nullable()->constrained('clinic_payment_accounts')->onDelete('set null');
            $table->enum('billing_type', ['individual', 'account'])->default('individual');
            
            // Index for performance
            $table->index(['clinic_payment_account_id', 'billing_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropForeign(['clinic_payment_account_id']);
            $table->dropColumn(['clinic_payment_account_id', 'billing_type']);
        });
    }
};
