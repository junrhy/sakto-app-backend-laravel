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
                $table->string('client_identifier');
                $table->foreignId('shipment_id')->constrained('transportation_shipment_trackings')->onDelete('cascade');
                $table->string('name');
                $table->integer('quantity');
                $table->enum('unit', ['kg', 'pieces', 'pallets', 'boxes']);
                $table->text('description')->nullable();
                $table->string('special_handling')->nullable();
                $table->enum('status', ['Loaded', 'In Transit', 'Delivered', 'Damaged'])->default('Loaded');
                $table->decimal('temperature', 5, 2)->nullable(); // in Celsius
                $table->decimal('humidity', 5, 2)->nullable(); // percentage
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
        if (Schema::hasTable('transportation_cargo_monitorings')) {
            $count = DB::table('transportation_cargo_monitorings')->count();
            if ($count === 0) {
                Schema::dropIfExists('transportation_cargo_monitorings');
            }
        }
    }
};
