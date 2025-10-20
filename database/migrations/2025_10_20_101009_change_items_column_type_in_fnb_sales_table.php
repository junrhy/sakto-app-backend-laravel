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
            // Change items column from VARCHAR(255) to TEXT to handle larger JSON data
            $table->text('items')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fnb_sales', function (Blueprint $table) {
            // Revert back to string (not recommended, but for rollback purposes)
            $table->string('items')->change();
        });
    }
};
