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
        if (!Schema::hasTable('event_participants')) {
            Schema::create('event_participants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
                $table->string('name');
                $table->string('email');
                $table->string('phone')->nullable();
                $table->text('notes')->nullable();
                $table->boolean('checked_in')->default(false);
                $table->timestamp('checked_in_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('event_participants')) {
            $count = DB::table('event_participants')->count();
            if ($count > 0) {
                Schema::dropIfExists('event_participants');
            }
        }
    }
};
