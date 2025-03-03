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
        if (!Schema::hasTable('fnb_sales')) {
            Schema::create('fnb_sales', function (Blueprint $table) {
                $table->id();
                $table->string('table_number');
                $table->string('items');
                $table->decimal('subtotal', 10, 2);
                $table->decimal('discount', 10, 2)->nullable();
                $table->string('discount_type')->nullable();
                $table->decimal('total', 10, 2);
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
        if (Schema::hasTable('fnb_sales')) {
            $count = DB::table('fnb_sales')->count();
            if ($count === 0) {
                Schema::dropIfExists('fnb_sales');
            }
        }
    }
};
