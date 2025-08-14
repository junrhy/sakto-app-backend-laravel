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
        Schema::create('biller_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('biller_id')->constrained('billers')->onDelete('cascade');
            $table->foreignId('contact_id')->constrained('contacts')->onDelete('cascade');
            $table->string('client_identifier');
            $table->timestamps();

            // Ensure a contact can only favorite a biller once
            $table->unique(['biller_id', 'contact_id', 'client_identifier']);
            
            // Index for faster queries
            $table->index(['contact_id', 'client_identifier']);
            $table->index(['biller_id', 'client_identifier']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('biller_favorites');
    }
};
