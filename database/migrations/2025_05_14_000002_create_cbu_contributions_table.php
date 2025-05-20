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
        if (!Schema::hasTable('cbu_contributions')) {
            Schema::create('cbu_contributions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cbu_fund_id')->constrained()->onDelete('cascade');
                $table->decimal('amount', 10, 2);
                $table->dateTime('contribution_date');
                $table->text('notes')->nullable();
                $table->string('contributor_name')->nullable();
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
        if (Schema::hasTable('cbu_contributions')) {
            $count = DB::table('cbu_contributions')->count();
            if ($count === 0) {
                Schema::dropIfExists('cbu_contributions');
            }
        }
    }
}; 