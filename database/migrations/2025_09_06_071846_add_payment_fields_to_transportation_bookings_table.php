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
        Schema::table('transportation_bookings', function (Blueprint $table) {
            // Payment fields
            $table->string('payment_method')->nullable()->after('pricing_version'); // cash, card, bank_transfer, digital_wallet
            $table->string('payment_status')->default('pending')->after('payment_method'); // pending, paid, failed, refunded
            $table->string('payment_reference')->nullable()->after('payment_status');
            $table->decimal('paid_amount', 10, 2)->nullable()->after('payment_reference');
            $table->timestamp('payment_date')->nullable()->after('paid_amount');
            $table->text('payment_notes')->nullable()->after('payment_date');
            
            // Add index for payment status queries
            $table->index(['client_identifier', 'payment_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transportation_bookings', function (Blueprint $table) {
            $table->dropIndex(['client_identifier', 'payment_status']);
            $table->dropColumn([
                'payment_method',
                'payment_status',
                'payment_reference',
                'paid_amount',
                'payment_date',
                'payment_notes'
            ]);
        });
    }
};