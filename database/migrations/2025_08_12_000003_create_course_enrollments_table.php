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
        Schema::create('course_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->string('contact_id');
            $table->string('student_name');
            $table->string('student_email');
            $table->string('student_phone')->nullable();
            $table->enum('status', ['active', 'completed', 'cancelled', 'expired'])->default('active');
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->decimal('amount_paid', 10, 2)->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->integer('progress_percentage')->default(0);
            $table->integer('lessons_completed')->default(0);
            $table->json('certificate_data')->nullable();
            $table->string('client_identifier');
            $table->timestamps();

            // Indexes
            $table->index(['course_id', 'status']);
            $table->index(['contact_id', 'status']);
            $table->index(['client_identifier', 'status']);
            $table->index('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_enrollments');
    }
};
