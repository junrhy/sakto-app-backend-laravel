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
        if (!Schema::hasTable('fnb_tables')) {
            Schema::create('fnb_tables', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->integer('seats');
                $table->enum('status', ['available', 'occupied', 'reserved', 'joined']);
                $table->string('joined_with')->nullable();
                $table->string('client_identifier')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('fnb_tables')) {
            $count = DB::table('fnb_tables')->count();
            if ($count === 0) {
                Schema::dropIfExists('fnb_tables');
            }
        }
    }
};
