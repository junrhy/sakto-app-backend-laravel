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
        Schema::create('clinic_payment_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier')->index(); // Required for backend API tables
            $table->string('account_type'); // 'group' or 'company'
            $table->string('account_name');
            $table->string('account_code')->unique(); // Unique identifier for the account
            $table->text('description')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('address')->nullable();
            $table->decimal('credit_limit', 12, 2)->default(0); // Optional credit limit
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->json('billing_settings')->nullable(); // JSON for billing preferences
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['client_identifier', 'account_type']);
            $table->index(['client_identifier', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinic_payment_accounts');
    }
};
