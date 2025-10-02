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
        Schema::create('salary_history', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier')->index();
            $table->unsignedBigInteger('employee_id');
            $table->decimal('previous_salary', 10, 2);
            $table->decimal('new_salary', 10, 2);
            $table->decimal('salary_change', 10, 2);
            $table->decimal('percentage_change', 5, 2);
            $table->string('change_reason');
            $table->string('approved_by');
            $table->date('effective_date');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['client_identifier', 'employee_id']);
            $table->index(['client_identifier', 'effective_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_history');
    }
};
