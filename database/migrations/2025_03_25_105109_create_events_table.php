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
        if (!Schema::hasTable('events')) {
            Schema::create('events', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description');
                $table->date('start_date');
                $table->date('end_date');
                $table->string('location');
                $table->integer('max_participants')->nullable();
                $table->date('registration_deadline')->nullable();
                $table->boolean('is_public')->default(false);
                $table->string('category');
                $table->string('image')->nullable();
                $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
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
        if (Schema::hasTable('events')) {
            // First check if the event_participants table exists
            if (Schema::hasTable('event_participants')) {
                // Get the actual constraint name from PostgreSQL
                $constraintName = DB::select("
                    SELECT tc.constraint_name
                    FROM information_schema.table_constraints tc
                    WHERE tc.table_name = 'event_participants'
                    AND tc.constraint_type = 'FOREIGN KEY'
                    AND tc.constraint_name LIKE '%event_id%'
                ");

                if (!empty($constraintName)) {
                    Schema::table('event_participants', function (Blueprint $table) use ($constraintName) {
                        $table->dropForeign($constraintName[0]->constraint_name);
                    });
                }
            }
            
            // Then drop the table
            Schema::dropIfExists('events');
        }
    }
};
