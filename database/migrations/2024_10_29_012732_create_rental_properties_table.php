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
        if (!Schema::hasTable('rental_properties')) {
            Schema::create('rental_properties', function (Blueprint $table) {
                $table->id();
                $table->string('address');
                $table->string('type');
                $table->integer('bedrooms');
                $table->integer('bathrooms');
                $table->decimal('rent', 10, 2);
                $table->string('status');
                $table->string('tenant_name')->nullable();
                $table->date('lease_start')->nullable();
                $table->date('lease_end')->nullable();
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
        if (Schema::hasTable('rental_properties')) {
            $count = DB::table('rental_properties')->count();
            if ($count === 0) {
                Schema::dropIfExists('rental_properties');
            }
        }
    }
};
