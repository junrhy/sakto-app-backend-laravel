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
        if (!Schema::hasTable('transportation_tracking_updates')) {
            Schema::create('transportation_tracking_updates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shipment_id')->constrained('transportation_shipment_trackings')->onDelete('cascade');
                $table->enum('status', ['Scheduled', 'In Transit', 'Delivered', 'Delayed']);
                $table->string('location');
                $table->timestamp('timestamp');
                $table->text('notes')->nullable();
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
        if (Schema::hasTable('transportation_tracking_updates')) {
            $count = DB::table('transportation_tracking_updates')->count();
            if ($count === 0) {
                Schema::dropIfExists('transportation_tracking_updates');
            }
        }
    }
};
