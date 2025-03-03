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
        if (!Schema::hasTable('family_members')) {
            Schema::create('family_members', function (Blueprint $table) {
                $table->id();
                $table->string('first_name');
                $table->string('last_name');
                $table->date('birth_date')->nullable();
                $table->date('death_date')->nullable();
                $table->enum('gender', ['male', 'female', 'other']);
                $table->string('photo')->nullable();
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
        if (Schema::hasTable('family_members')) {
            $count = DB::table('family_members')->count();
            if ($count === 0) {
                Schema::dropIfExists('family_members');
            }
        }
    }
};
