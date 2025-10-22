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
            $table->enum('order_source', ['staff', 'customer'])->default('staff')->after('client_identifier');
            $table->string('customer_name')->nullable()->after('table_name');
            $table->text('customer_notes')->nullable()->after('items');
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
