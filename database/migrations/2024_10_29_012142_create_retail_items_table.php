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
        if (!Schema::hasTable('retail_items')) {
            Schema::create('retail_items', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('sku');
                $table->json('images')->nullable();
                $table->integer('quantity');
                $table->string('unit')->nullable();
                $table->decimal('price');
                $table->foreignId('category_id')->constrained('retail_categories');
                $table->text('barcode')->nullable();
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
        if (Schema::hasTable('retail_items')) {
            $count = DB::table('retail_items')->count();
            if ($count === 0) {
                Schema::dropIfExists('retail_items');
            }
        }
    }
};
