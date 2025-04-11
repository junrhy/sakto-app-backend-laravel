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
        if (!Schema::hasTable('challenge_participants')) {
            Schema::create('challenge_participants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('challenge_id')->constrained('challenges');
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('address')->nullable();
                $table->string('city')->nullable();
                $table->string('state')->nullable();
                $table->string('country')->nullable();
                $table->string('zip_code')->nullable();
                $table->string('status')->default('pending');
                $table->string('progress')->default(0);
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
        if (Schema::hasTable('challenge_participants')) {
            $count = DB::table('challenge_participants')->count();
            if ($count === 0) {
                Schema::dropIfExists('challenge_participants');
            }
        }
    }
};
