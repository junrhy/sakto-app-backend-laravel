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
        Schema::table('transportation_fleets', function (Blueprint $table) {
            $table->decimal('current_latitude', 10, 8)->nullable()->after('driver_contact');
            $table->decimal('current_longitude', 11, 8)->nullable()->after('current_latitude');
            $table->timestamp('last_location_update')->nullable()->after('current_longitude');
            $table->string('current_address')->nullable()->after('last_location_update');
            $table->decimal('speed', 5, 2)->nullable()->after('current_address'); // km/h
            $table->decimal('heading', 5, 2)->nullable()->after('speed'); // degrees 0-360
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transportation_fleets', function (Blueprint $table) {
            $table->dropColumn([
                'current_latitude',
                'current_longitude', 
                'last_location_update',
                'current_address',
                'speed',
                'heading'
            ]);
        });
    }
};
