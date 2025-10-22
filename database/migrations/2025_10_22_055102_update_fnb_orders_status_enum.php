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
        // For PostgreSQL, we need to handle enum changes differently
        if (DB::getDriverName() === 'pgsql') {
            // PostgreSQL: Drop the existing column and recreate it
            Schema::table('fnb_orders', function (Blueprint $table) {
                $table->dropColumn('status');
            });
            
            Schema::table('fnb_orders', function (Blueprint $table) {
                $table->string('status')->default('active')->after('client_identifier');
            });
            
            // Add check constraint for PostgreSQL
            DB::statement("ALTER TABLE fnb_orders ADD CONSTRAINT fnb_orders_status_check CHECK (status IN ('pending', 'active', 'completed', 'cancelled'))");
        } else {
            // MySQL: Use the original MODIFY COLUMN syntax
            DB::statement("ALTER TABLE fnb_orders MODIFY COLUMN status ENUM('pending', 'active', 'completed', 'cancelled') DEFAULT 'active'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            // PostgreSQL: Drop constraint and recreate column
            DB::statement("ALTER TABLE fnb_orders DROP CONSTRAINT IF EXISTS fnb_orders_status_check");
            
            Schema::table('fnb_orders', function (Blueprint $table) {
                $table->dropColumn('status');
            });
            
            Schema::table('fnb_orders', function (Blueprint $table) {
                $table->string('status')->default('active')->after('client_identifier');
            });
            
            // Add original check constraint
            DB::statement("ALTER TABLE fnb_orders ADD CONSTRAINT fnb_orders_status_check CHECK (status IN ('active', 'completed', 'cancelled'))");
        } else {
            // MySQL: Revert back to original enum values
            DB::statement("ALTER TABLE fnb_orders MODIFY COLUMN status ENUM('active', 'completed', 'cancelled') DEFAULT 'active'");
        }
    }
};
