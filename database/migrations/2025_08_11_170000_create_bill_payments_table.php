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
        if (!Schema::hasTable('bill_payments')) {
            Schema::create('bill_payments', function (Blueprint $table) {
                $table->id();
                $table->string('bill_number')->unique();
                $table->string('bill_title');
                $table->foreignId('biller_id')->constrained('billers');
                $table->text('bill_description')->nullable();
                $table->decimal('amount', 10, 2);
                $table->date('due_date');
                $table->date('payment_date')->nullable();
                $table->enum('status', ['pending', 'paid', 'overdue', 'cancelled', 'partial'])->default('pending');
                $table->string('payment_method')->nullable();
                $table->string('reference_number')->nullable();
                $table->text('notes')->nullable();
                $table->string('client_identifier');
                $table->string('category')->nullable();
                $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
                $table->boolean('is_recurring')->default(false);
                $table->enum('recurring_frequency', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])->nullable();
                $table->date('next_due_date')->nullable();
                $table->json('attachments')->nullable();
                $table->boolean('reminder_sent')->default(false);
                $table->date('reminder_date')->nullable();
                $table->timestamps();

                $table->index(['client_identifier', 'status']);
                $table->index(['due_date', 'status']);
                $table->index(['category', 'status']);
                $table->index(['priority', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('bill_payments')) {
            $count = DB::table('bill_payments')->count();
            if ($count === 0) {
                Schema::dropIfExists('bill_payments');
            }
        }
    }
};
