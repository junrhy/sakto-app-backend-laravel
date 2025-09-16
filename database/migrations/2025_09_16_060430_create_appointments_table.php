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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier');
            $table->unsignedBigInteger('patient_id');
            $table->string('patient_name');
            $table->string('patient_phone')->nullable();
            $table->string('patient_email')->nullable();
            $table->datetime('appointment_date');
            $table->string('appointment_time');
            $table->string('appointment_type')->default('consultation'); // consultation, follow_up, emergency, etc.
            $table->text('notes')->nullable();
            $table->string('status')->default('scheduled'); // scheduled, confirmed, completed, cancelled, no_show
            $table->string('doctor_name')->nullable();
            $table->decimal('fee', 10, 2)->nullable();
            $table->string('payment_status')->default('pending'); // pending, paid, partial
            $table->text('cancellation_reason')->nullable();
            $table->datetime('cancelled_at')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->index(['client_identifier', 'appointment_date']);
            $table->index(['status', 'appointment_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
