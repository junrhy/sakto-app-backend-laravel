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
        Schema::table('fnb_blocked_dates', function (Blueprint $table) {
            $table->time('start_time')->nullable()->after('blocked_date');
            $table->time('end_time')->nullable()->after('start_time');
            $table->boolean('is_full_day')->default(true)->after('end_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fnb_blocked_dates', function (Blueprint $table) {
            $table->dropColumn(['start_time', 'end_time', 'is_full_day']);
        });
    }
};
