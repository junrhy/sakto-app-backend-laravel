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
        Schema::create('billers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->text('address')->nullable();
            $table->string('account_number')->nullable();
            $table->string('category')->nullable(); // e.g., utilities, insurance, loans, etc.
            $table->boolean('is_active')->default(true);
            $table->string('client_identifier'); // to associate with specific client
            $table->timestamps();

            $table->index(['client_identifier', 'is_active']);
            $table->index(['category', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billers');
    }
};
