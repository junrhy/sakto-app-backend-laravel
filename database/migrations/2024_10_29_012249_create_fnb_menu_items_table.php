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
        if (!Schema::hasTable('fnb_menu_items')) {
            Schema::create('fnb_menu_items', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->decimal('price', 8, 2);
                $table->string('category');
                $table->string('image')->nullable();
                $table->boolean('is_available_personal')->default(true);
                $table->boolean('is_available_online')->default(true);
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
        if (Schema::hasTable('fnb_menu_items')) {
            $count = DB::table('fnb_menu_items')->count();
            if ($count === 0) {
                Schema::dropIfExists('fnb_menu_items');
            }
        }
    }
};
