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
        if (!Schema::hasTable('retail_sales')) {
            Schema::create('retail_sales', function (Blueprint $table) {
                $table->id();
                $table->json('items');
                $table->decimal('total_amount');
                $table->decimal('cash_received')->nullable();
                $table->decimal('change')->nullable();
                $table->string('payment_method')->nullable();
                $table->string('client_identifier')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('retail_sales')) {
            $count = DB::table('retail_sales')->count();
            if ($count === 0) {
                Schema::dropIfExists('retail_sales');
            }
        }
    }
};
