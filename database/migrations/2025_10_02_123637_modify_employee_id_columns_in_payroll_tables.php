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
        // Change employee_id column type from integer to string in salary_history table
        Schema::table('salary_history', function (Blueprint $table) {
            $table->string('employee_id')->change();
        });

        // Change employee_id column type from integer to string in time_tracking table
        Schema::table('time_tracking', function (Blueprint $table) {
            $table->string('employee_id')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert employee_id column type back to integer in salary_history table
        Schema::table('salary_history', function (Blueprint $table) {
            $table->integer('employee_id')->change();
        });

        // Revert employee_id column type back to integer in time_tracking table
        Schema::table('time_tracking', function (Blueprint $table) {
            $table->integer('employee_id')->change();
        });
    }
};
