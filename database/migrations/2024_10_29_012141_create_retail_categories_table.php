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
        if (!Schema::hasTable('retail_categories')) {
            Schema::create('retail_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
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
        if (Schema::hasTable('retail_categories')) {
            $count = DB::table('retail_categories')->count();
            if ($count === 0) {
                Schema::dropIfExists('retail_categories');
            }
        }
    }
};
