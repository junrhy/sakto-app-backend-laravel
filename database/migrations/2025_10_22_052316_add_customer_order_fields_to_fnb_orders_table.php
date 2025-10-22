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
        Schema::table('fnb_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('fnb_orders', 'order_source')) {
                $table->enum('order_source', ['staff', 'customer'])->default('staff');
            }
            if (!Schema::hasColumn('fnb_orders', 'customer_name')) {
                $table->string('customer_name')->nullable();
            }
            if (!Schema::hasColumn('fnb_orders', 'customer_notes')) {
                $table->text('customer_notes')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fnb_orders', function (Blueprint $table) {
            $table->dropColumn(['order_source', 'customer_name', 'customer_notes']);
        });
    }
};
