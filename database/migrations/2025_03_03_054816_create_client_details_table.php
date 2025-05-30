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
        if (!Schema::hasTable('client_details')) {
            Schema::create('client_details', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->constrained('clients');
                $table->string('app_name');
                $table->string('name');
                $table->string('value');
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
        if (Schema::hasTable('client_details')) {
            $count = DB::table('client_details')->count();
            if ($count > 0) {
                Schema::dropIfExists('client_details');
            }   
        }
    }
};
