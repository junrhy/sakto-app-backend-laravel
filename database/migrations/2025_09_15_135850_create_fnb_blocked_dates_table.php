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
        Schema::create('fnb_blocked_dates', function (Blueprint $table) {
            $table->id();
            $table->date('blocked_date');
            $table->string('reason')->nullable();
            $table->string('client_identifier');
            $table->timestamps();
            
            // Add index for better performance
            $table->index(['blocked_date', 'client_identifier']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fnb_blocked_dates');
    }
};
