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
        if (!Schema::hasTable('contacts')) {
            Schema::create('contacts', function (Blueprint $table) {
                $table->id();
                $table->string('first_name');
                $table->string('middle_name')->nullable();
                $table->string('last_name');
                $table->string('gender');
                $table->string('fathers_name')->nullable();
                $table->string('mothers_maiden_name')->nullable();
                $table->string('email')->nullable();
                $table->string('call_number')->nullable();
                $table->string('sms_number')->nullable();
                $table->string('whatsapp')->nullable();
                $table->string('facebook')->nullable();
                $table->string('instagram')->nullable();
                $table->string('twitter')->nullable();
                $table->string('linkedin')->nullable();
                $table->string('address')->nullable();
                $table->text('notes')->nullable();
                $table->string('id_picture')->nullable();
                $table->json('id_numbers')->nullable();
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
        if (Schema::hasTable('contacts')) {
            $count = DB::table('contacts')->count();
            if ($count === 0) {
                Schema::dropIfExists('contacts');
            }
        }
    }
};
