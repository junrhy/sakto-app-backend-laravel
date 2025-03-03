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
        if (!Schema::hasTable('patient_payments')) {
            Schema::create('patient_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
                $table->string('payment_date');
                $table->string('payment_amount');
                $table->string('payment_method')->nullable();
                $table->string('payment_notes')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('patient_payments')) {
            $count = DB::table('patient_payments')->count();
            if ($count === 0) {
                Schema::dropIfExists('patient_payments');
            }
        }
    }
};
