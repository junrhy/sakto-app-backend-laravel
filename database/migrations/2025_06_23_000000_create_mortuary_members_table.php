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
        Schema::create('mortuary_members', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier');
            $table->string('name');
            $table->date('date_of_birth');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->string('contact_number', 20);
            $table->text('address');
            $table->date('membership_start_date');
            $table->decimal('contribution_amount', 10, 2);
            $table->enum('contribution_frequency', ['monthly', 'quarterly', 'annually']);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('group')->nullable();
            $table->timestamps();

            $table->index('client_identifier');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mortuary_members');
    }
}; 