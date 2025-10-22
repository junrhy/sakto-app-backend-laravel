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
            // Add item_status column to track individual item status
            // This will be a JSON column storing status for each item
            // Example: {"1": "sent_to_kitchen", "2": "pending", "3": "sent_to_kitchen"}
            $table->json('item_status')->nullable()->after('items');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fnb_orders', function (Blueprint $table) {
            $table->dropColumn('item_status');
        });
    }
};