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
        Schema::create('fnb_daily_notes', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier')->index();
            $table->date('note_date')->index();
            $table->text('note');
            $table->string('created_by')->nullable(); // User name or identifier who created the note
            $table->timestamps();
            
            // Index for performance
            $table->index(['client_identifier', 'note_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fnb_daily_notes');
    }
};
