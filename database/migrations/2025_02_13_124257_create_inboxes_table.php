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
        if (!Schema::hasTable('inboxes')) {
            Schema::create('inboxes', function (Blueprint $table) {
                $table->id();
                $table->string('client_identifier');
                $table->string('subject');
                $table->text('message');
                $table->string('type')->default('notification'); // notification, alert, message, etc.
                $table->boolean('is_read')->default(false);
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('inboxes')) {
            $count = DB::table('inboxes')->count();
            if ($count === 0) {
                Schema::dropIfExists('inboxes');
            }
        }
    }
};
