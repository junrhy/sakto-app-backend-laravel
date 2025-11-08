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
        Schema::create('handyman_work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier')->index();
            $table->string('reference_number')->index();
            $table->string('status')->default('draft'); // draft, assigned, in_progress, awaiting_approval, completed, cancelled
            $table->foreignId('task_id')->nullable()->constrained('handyman_tasks')->nullOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('handyman_technicians')->nullOnDelete();
            $table->string('customer_name')->nullable();
            $table->string('customer_contact')->nullable();
            $table->string('customer_address')->nullable();
            $table->text('scope_of_work')->nullable();
            $table->json('materials')->nullable();
            $table->json('checklist')->nullable();
            $table->json('approval')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamps();

            $table->index(['client_identifier', 'status']);
            $table->index(['client_identifier', 'scheduled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('handyman_work_orders');
    }
};

