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
        Schema::create('handyman_task_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier')->index();
            $table->foreignId('task_id')->constrained('handyman_tasks')->onDelete('cascade');
            $table->foreignId('technician_id')->constrained('handyman_technicians')->onDelete('cascade');
            $table->dateTime('assigned_start_at')->nullable();
            $table->dateTime('assigned_end_at')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->string('conflict_status')->default('none'); // none, overlap, double_booked
            $table->timestamps();

            $table->index(['client_identifier', 'technician_id'], 'handyman_task_assign_tech_idx');
            $table->index(['client_identifier', 'assigned_start_at'], 'handyman_task_assign_start_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('handyman_task_assignments');
    }
};

