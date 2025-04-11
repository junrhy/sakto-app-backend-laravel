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
        if (!Schema::hasTable('challenges')) {
            Schema::create('challenges', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('description');
                $table->date('start_date');
                $table->date('end_date');
                $table->string('goal_type');
                $table->integer('goal_value');
                $table->string('goal_unit');
                $table->string('visibility');
                $table->json('rewards');
                $table->string('status');
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
        if (Schema::hasTable('challenges')) {
            $count = DB::table('challenges')->count();
            if ($count === 0) {
                Schema::dropIfExists('challenges');
            }
        }
    }
};
