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
        if (!Schema::hasTable('loan_bills')) {
            Schema::create('loan_bills', function (Blueprint $table) {
                $table->id();
                $table->foreignId('loan_id')->constrained('loans')->onDelete('cascade');
                $table->integer('bill_number');
                $table->date('due_date');
                $table->decimal('principal', 10, 2);
                $table->decimal('interest', 10, 2);
                $table->decimal('total_amount', 10, 2);
                $table->decimal('total_amount_due', 10, 2);
                $table->decimal('installment_amount', 10, 2)->nullable();
                $table->decimal('penalty_amount', 10, 2)->default(0);
                $table->string('note')->nullable();
                $table->string('status');
                $table->string('client_identifier');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('loan_bills')) {
            $count = DB::table('loan_bills')->count();
            if ($count === 0) {
                Schema::dropIfExists('loan_bills');
            }
        }
    }
};
