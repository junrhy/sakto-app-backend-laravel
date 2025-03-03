<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('credit_spent_histories')) {
            Schema::create('credit_spent_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('credit_id')->constrained('credits')->onDelete('cascade');
                $table->string('client_identifier');
                $table->integer('amount');
                $table->string('purpose');
                $table->string('reference_id');
                $table->string('status');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('credit_spent_histories')) {
            $count = DB::table('credit_spent_histories')->count();
            if ($count === 0) {
                Schema::dropIfExists('credit_spent_histories');
            }
        }
    }
};
