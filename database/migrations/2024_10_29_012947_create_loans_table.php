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
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->string('borrower_name');
            $table->decimal('amount', 10, 2);
            $table->decimal('interest_rate', 5, 2);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('interest_type')->default('fixed');
            $table->string('compounding_frequency')->default('monthly');
            $table->string('status');
            $table->decimal('total_interest', 10, 2);
            $table->decimal('total_balance', 10, 2);
            $table->decimal('paid_amount', 10, 2);
            $table->string('client_identifier');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
