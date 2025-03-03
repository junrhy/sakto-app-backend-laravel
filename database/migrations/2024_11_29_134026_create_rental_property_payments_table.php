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
        if (!Schema::hasTable('rental_property_payments')) {
            Schema::create('rental_property_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rental_property_id')->constrained('rental_properties')->onDelete('cascade');
                $table->decimal('amount', 10, 2);
                $table->date('payment_date');
                $table->string('reference')->nullable();
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
        if (Schema::hasTable('rental_property_payments')) {
            $count = DB::table('rental_property_payments')->count();
            if ($count === 0) {
                Schema::dropIfExists('rental_property_payments');
            }
        }
    }
};
