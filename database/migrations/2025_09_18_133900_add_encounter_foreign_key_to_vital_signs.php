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
        Schema::table('patient_vital_signs', function (Blueprint $table) {
            $table->foreign('encounter_id')->references('id')->on('patient_encounters')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patient_vital_signs', function (Blueprint $table) {
            $table->dropForeign(['encounter_id']);
        });
    }
};