<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update the status enum to include 'pending' for customer orders
        DB::statement("ALTER TABLE fnb_orders MODIFY COLUMN status ENUM('pending', 'active', 'completed', 'cancelled') DEFAULT 'active'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum values
        DB::statement("ALTER TABLE fnb_orders MODIFY COLUMN status ENUM('active', 'completed', 'cancelled') DEFAULT 'active'");
    }
};
