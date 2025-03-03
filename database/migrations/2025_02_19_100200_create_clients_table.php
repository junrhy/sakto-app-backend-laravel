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
        if (!Schema::hasTable('clients')) {
            Schema::create('clients', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('client_identifier');
                $table->string('email');
                $table->string('contact_number')->nullable();
                $table->string('referrer');
                $table->boolean('active')->default(true);
                $table->timestamps();
            }); 
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('clients')) {
            $count = DB::table('clients')->count();
            if ($count === 0) {
                Schema::dropIfExists('clients');
            }
        }
    }
};
