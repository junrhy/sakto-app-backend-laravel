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
        // Check if table_number column exists before trying to rename it
        if (Schema::hasColumn('fnb_orders', 'table_number')) {
            Schema::table('fnb_orders', function (Blueprint $table) {
                $table->renameColumn('table_number', 'table_name');
            });
        } else {
            // If table_number doesn't exist, check if table_name already exists
            if (!Schema::hasColumn('fnb_orders', 'table_name')) {
                // Add table_name column if neither exists
                Schema::table('fnb_orders', function (Blueprint $table) {
                    $table->string('table_name')->after('client_identifier');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if table_name column exists before trying to rename it back
        if (Schema::hasColumn('fnb_orders', 'table_name')) {
            Schema::table('fnb_orders', function (Blueprint $table) {
                $table->renameColumn('table_name', 'table_number');
            });
        } else {
            // If table_name doesn't exist, check if table_number already exists
            if (!Schema::hasColumn('fnb_orders', 'table_number')) {
                // Add table_number column if neither exists
                Schema::table('fnb_orders', function (Blueprint $table) {
                    $table->string('table_number')->after('client_identifier');
                });
            }
        }
    }
};
