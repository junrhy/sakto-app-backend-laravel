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
        if (!Schema::hasTable('cbu_histories')) {
            Schema::create('cbu_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cbu_fund_id')->constrained()->onDelete('cascade');
                $table->enum('action', [
                'contribution', 
                'withdrawal_request', 
                'withdrawal', 
                'fund_creation', 
                'fund_update',
                'dividend'
            ]);
            $table->decimal('amount', 10, 2);
            $table->text('notes')->nullable();
                $table->dateTime('date');
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
        if (Schema::hasTable('cbu_histories')) {
            $count = DB::table('cbu_histories')->count();
            if ($count === 0) {
                Schema::dropIfExists('cbu_histories');
            }
        }
    }
}; 