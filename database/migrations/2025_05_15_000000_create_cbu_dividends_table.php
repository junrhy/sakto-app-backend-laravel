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
        if (!Schema::hasTable('cbu_dividends')) {
            Schema::create('cbu_dividends', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cbu_fund_id')->constrained('cbu_funds')->onDelete('cascade');
                $table->decimal('amount', 10, 2);
                $table->date('dividend_date');
                $table->text('notes')->nullable();
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
        if (Schema::hasTable('cbu_dividends')) {
            $count = DB::table('cbu_dividends')->count();
            if ($count === 0) {
                Schema::dropIfExists('cbu_dividends');
            }
        }
    }
}; 