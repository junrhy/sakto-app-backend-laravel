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
        Schema::table('event_participants', function (Blueprint $table) {
            $table->enum('payment_status', ['pending', 'paid', 'cancelled'])->default('pending')->after('checked_in_at');
            $table->decimal('amount_paid', 10, 2)->nullable()->after('payment_status');
            $table->timestamp('payment_date')->nullable()->after('amount_paid');
            $table->string('payment_method')->nullable()->after('payment_date');
            $table->string('transaction_id')->nullable()->after('payment_method');
            $table->text('payment_notes')->nullable()->after('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_participants', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'amount_paid', 'payment_date', 'payment_method', 'transaction_id', 'payment_notes']);
        });
    }
}; 