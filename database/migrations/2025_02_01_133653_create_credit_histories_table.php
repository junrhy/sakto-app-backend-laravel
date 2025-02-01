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
        Schema::create('credit_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_id')->constrained('credits')->onDelete('cascade');
            $table->string('client_identifier');
            $table->string('package_name');
            $table->integer('package_credit');
            $table->integer('package_amount');
            $table->string('payment_method');
            $table->string('payment_method_details')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('proof_of_payment')->nullable();
            $table->string('status');
            $table->dateTime('approved_date')->nullable();
            $table->string('approved_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_histories');
    }
};
