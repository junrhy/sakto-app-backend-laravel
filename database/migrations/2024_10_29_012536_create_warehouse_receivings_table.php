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
        if (!Schema::hasTable('warehouse_receivings')) {
            Schema::create('warehouse_receivings', function (Blueprint $table) {
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
        if (Schema::hasTable('warehouse_receivings')) {
            $count = DB::table('warehouse_receivings')->count();
            if ($count === 0) {
                Schema::dropIfExists('warehouse_receivings');
            }
        }
    }
};
