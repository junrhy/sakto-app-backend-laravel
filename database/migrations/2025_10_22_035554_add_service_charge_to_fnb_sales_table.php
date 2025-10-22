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
            $table->decimal('service_charge', 10, 2)->default(0)->after('discount_type');
            $table->enum('service_charge_type', ['percentage', 'fixed'])->default('percentage')->after('service_charge');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fnb_sales', function (Blueprint $table) {
            $table->dropColumn(['service_charge', 'service_charge_type']);
        });
    }
};
