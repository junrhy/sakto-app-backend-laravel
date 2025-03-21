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
        if (!Schema::hasTable('patient_dental_charts')) {
            Schema::create('patient_dental_charts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
                $table->string('tooth_id');
                $table->string('status');
                $table->string('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('patient_dental_charts')) {
            $count = DB::table('patient_dental_charts')->count();
            if ($count === 0) {
                Schema::dropIfExists('patient_dental_charts');
            }
        }
    }
};
