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
        if (!Schema::hasTable('loans')) {
            Schema::create('loans', function (Blueprint $table) {
                $table->id();
                $table->string('borrower_name');
                $table->decimal('amount', 10, 2);
                $table->decimal('interest_rate', 5, 2);
                $table->date('start_date');
                $table->date('end_date');
                $table->string('interest_type')->default('fixed');
                $table->string('frequency')->default('monthly');
                $table->string('installment_frequency')->nullable();
                $table->decimal('installment_amount', 10, 2)->nullable();
                $table->string('status');
                $table->decimal('total_interest', 10, 2)->default(0);
                $table->decimal('total_balance', 10, 2)->default(0);
                $table->decimal('paid_amount', 10, 2)->default(0);
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
        if (Schema::hasTable('loans')) {
            $count = DB::table('loans')->count();
            if ($count === 0) {
                Schema::dropIfExists('loans');
            }
        }
    }
};
