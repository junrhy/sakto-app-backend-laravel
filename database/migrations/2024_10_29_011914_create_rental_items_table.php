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
        if (!Schema::hasTable('rental_items')) {
            Schema::create('rental_items', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('category');
                $table->decimal('daily_rate', 10, 2);
                $table->integer('quantity');
                $table->string('status');
                $table->string('renter_name')->nullable();
                $table->string('renter_contact')->nullable();
                $table->string('rental_start')->nullable();
                $table->string('rental_end')->nullable();
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
        if (Schema::hasTable('rental_items')) {
            $count = DB::table('rental_items')->count();
            if ($count === 0) {
                Schema::dropIfExists('rental_items');
            }
        }
    }
};
