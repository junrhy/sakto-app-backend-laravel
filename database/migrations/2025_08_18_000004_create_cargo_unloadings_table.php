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
        if (!Schema::hasTable('cargo_unloadings')) {
            Schema::create('cargo_unloadings', function (Blueprint $table) {
                $table->id();
                $table->string('client_identifier');
                $table->foreignId('cargo_item_id')->constrained('transportation_cargo_monitorings')->onDelete('cascade');
                $table->integer('quantity_unloaded');
                $table->string('unload_location');
                $table->text('notes')->nullable();
                $table->timestamp('unloaded_at');
                $table->string('unloaded_by')->nullable(); // Driver or person who unloaded
                $table->timestamps();
                
                $table->index('client_identifier');
                $table->index('cargo_item_id');
                $table->index('unloaded_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('cargo_unloadings')) {
            $count = DB::table('cargo_unloadings')->count();
            if ($count === 0) {
                Schema::dropIfExists('cargo_unloadings');
            }
        }
    }
};
