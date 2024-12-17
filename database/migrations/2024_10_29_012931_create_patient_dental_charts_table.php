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
        Schema::create('patient_dental_charts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->json('tooth_1')->nullable();
            $table->json('tooth_2')->nullable();
            $table->json('tooth_3')->nullable();
            $table->json('tooth_4')->nullable();
            $table->json('tooth_5')->nullable();
            $table->json('tooth_6')->nullable();
            $table->json('tooth_7')->nullable();
            $table->json('tooth_8')->nullable();
            $table->json('tooth_9')->nullable();
            $table->json('tooth_10')->nullable();
            $table->json('tooth_11')->nullable();
            $table->json('tooth_12')->nullable();
            $table->json('tooth_13')->nullable();
            $table->json('tooth_14')->nullable();
            $table->json('tooth_15')->nullable();
            $table->json('tooth_16')->nullable();
            $table->json('tooth_17')->nullable();
            $table->json('tooth_18')->nullable();
            $table->json('tooth_19')->nullable();
            $table->json('tooth_20')->nullable();
            $table->json('tooth_21')->nullable();
            $table->json('tooth_22')->nullable();
            $table->json('tooth_23')->nullable();
            $table->json('tooth_24')->nullable();
            $table->json('tooth_25')->nullable();
            $table->json('tooth_26')->nullable();
            $table->json('tooth_27')->nullable();
            $table->json('tooth_28')->nullable();
            $table->json('tooth_29')->nullable();
            $table->json('tooth_30')->nullable();
            $table->json('tooth_31')->nullable();
            $table->json('tooth_32')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_dental_charts');
    }
};
