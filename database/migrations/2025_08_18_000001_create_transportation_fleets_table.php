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
        if (!Schema::hasTable('transportation_fleets')) {
            Schema::create('transportation_fleets', function (Blueprint $table) {
                $table->id();
                $table->string('plate_number')->unique();
                $table->string('model');
                $table->integer('capacity'); // in tons
                $table->enum('status', ['Available', 'In Transit', 'Maintenance'])->default('Available');
                $table->date('last_maintenance')->nullable();
                $table->decimal('fuel_level', 5, 2)->default(0); // percentage
                $table->integer('mileage')->default(0);
                $table->string('driver')->nullable();
                $table->string('driver_contact')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('transportation_fleets')) {
            $count = DB::table('transportation_fleets')->count();
            if ($count === 0) {
                Schema::dropIfExists('transportation_fleets');
            }
        }
    }
};
