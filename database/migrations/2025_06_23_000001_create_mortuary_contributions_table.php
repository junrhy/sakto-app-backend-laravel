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
        Schema::create('mortuary_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('mortuary_members')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->date('payment_date');
            $table->string('payment_method', 100);
            $table->string('reference_number', 100)->nullable();
            $table->timestamps();

            $table->index('member_id');
            $table->index('payment_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mortuary_contributions');
    }
}; 