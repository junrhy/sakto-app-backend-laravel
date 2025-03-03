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
        if (!Schema::hasTable('transportation_cargo_monitorings')) {
            Schema::create('transportation_cargo_monitorings', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('transportation_cargo_monitorings')) {
            $count = DB::table('transportation_cargo_monitorings')->count();
            if ($count === 0) {
                Schema::dropIfExists('transportation_cargo_monitorings');
            }
        }
    }
};
