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
        if (!Schema::hasTable('patient_checkups')) {
            Schema::create('patient_checkups', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
                $table->string('checkup_date');
                $table->string('diagnosis')->nullable();
                $table->string('treatment')->nullable();
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
        if (Schema::hasTable('patient_checkups')) {
            $count = DB::table('patient_checkups')->count();
            if ($count === 0) {
                Schema::dropIfExists('patient_checkups');
            }
        }
    }
};
