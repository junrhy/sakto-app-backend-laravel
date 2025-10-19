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
        Schema::table('fnb_sales', function (Blueprint $table) {
            $table->decimal('payment_amount', 10, 2)->nullable()->after('total');
            $table->string('payment_method')->nullable()->after('payment_amount'); // 'cash' or 'card'
            $table->decimal('change_amount', 10, 2)->default(0)->after('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fnb_sales', function (Blueprint $table) {
            $table->dropColumn(['payment_amount', 'payment_method', 'change_amount']);
        });
    }
};
