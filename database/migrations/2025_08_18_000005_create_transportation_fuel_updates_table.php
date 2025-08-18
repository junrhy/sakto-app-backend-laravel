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
        if (!Schema::hasTable('transportation_fuel_updates')) {
            Schema::create('transportation_fuel_updates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('truck_id')->constrained('transportation_fleets')->onDelete('cascade');
                $table->timestamp('timestamp');
                $table->decimal('previous_level', 5, 2); // percentage
                $table->decimal('new_level', 5, 2); // percentage
                $table->decimal('liters_added', 8, 2);
                $table->decimal('cost', 10, 2);
                $table->string('location');
                $table->string('updated_by');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('transportation_fuel_updates')) {
            $count = DB::table('transportation_fuel_updates')->count();
            if ($count === 0) {
                Schema::dropIfExists('transportation_fuel_updates');
            }
        }
    }
};
