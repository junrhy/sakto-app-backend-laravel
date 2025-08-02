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
        Schema::table('challenge_participants', function (Blueprint $table) {
            $table->timestamp('timer_started_at')->nullable()->after('progress');
            $table->timestamp('timer_ended_at')->nullable()->after('timer_started_at');
            $table->integer('timer_duration_seconds')->nullable()->after('timer_ended_at');
            $table->boolean('timer_is_active')->default(false)->after('timer_duration_seconds');
            $table->integer('elapsed_time_seconds')->default(0)->after('timer_is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('challenge_participants', function (Blueprint $table) {
            $table->dropColumn([
                'timer_started_at',
                'timer_ended_at', 
                'timer_duration_seconds',
                'timer_is_active',
                'elapsed_time_seconds'
            ]);
        });
    }
}; 