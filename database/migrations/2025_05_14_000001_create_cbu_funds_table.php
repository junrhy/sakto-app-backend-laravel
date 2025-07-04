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
        if (!Schema::hasTable('cbu_funds')) {
            Schema::create('cbu_funds', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('target_amount', 10, 2);
                $table->decimal('total_amount', 10, 2)->default(0);
                $table->decimal('value_per_share', 10, 2)->default(0);
                $table->integer('number_of_shares')->default(0);
                $table->enum('frequency', ['daily', 'weekly', 'monthly', 'quarterly', 'annually'])->default('monthly')->nullable();
                $table->dateTime('start_date');
                $table->dateTime('end_date')->nullable();
                $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
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
        if (Schema::hasTable('cbu_funds')) {
            $count = DB::table('cbu_funds')->count();
            if ($count === 0) {
                Schema::dropIfExists('cbu_funds');
            }
        }
    }
}; 