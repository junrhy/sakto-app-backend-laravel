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
        if (!Schema::hasTable('patient_bills')) {
            Schema::create('patient_bills', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
                $table->string('bill_number')->nullable();
                $table->string('bill_date');
                $table->decimal('bill_amount', 10, 2);
                $table->string('bill_status')->nullable();
                $table->string('bill_details')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('patient_bills')) {
            $count = DB::table('patient_bills')->count();
            if ($count === 0) {
                Schema::dropIfExists('patient_bills');
            }
        }
    }
};
