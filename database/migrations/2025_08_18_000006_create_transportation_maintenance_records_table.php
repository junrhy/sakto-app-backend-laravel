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
        if (!Schema::hasTable('transportation_maintenance_records')) {
            Schema::create('transportation_maintenance_records', function (Blueprint $table) {
                $table->id();
                $table->string('client_identifier');
                $table->foreignId('truck_id')->constrained('transportation_fleets')->onDelete('cascade');
                $table->date('date');
                $table->enum('type', ['Routine', 'Repair']);
                $table->text('description');
                $table->decimal('cost', 10, 2);
                $table->timestamps();
                
                $table->index('client_identifier');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('transportation_maintenance_records')) {
            $count = DB::table('transportation_maintenance_records')->count();
            if ($count === 0) {
                Schema::dropIfExists('transportation_maintenance_records');
            }
        }
    }
};
