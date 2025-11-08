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
        Schema::create('handyman_work_order_time_logs', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier')->index();
            $table->foreignId('work_order_id')->constrained('handyman_work_orders')->onDelete('cascade');
            $table->foreignId('technician_id')->nullable()->constrained('handyman_technicians')->nullOnDelete();
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['client_identifier', 'technician_id'], 'handyman_time_logs_tech_idx');
            $table->index(['client_identifier', 'started_at'], 'handyman_time_logs_start_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('handyman_work_order_time_logs');
    }
};

