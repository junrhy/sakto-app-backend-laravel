<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('handyman_inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier')->index();
            $table->string('sku')->nullable()->index();
            $table->string('name');
            $table->string('type')->default('tool'); // tool, consumable
            $table->string('category')->nullable();
            $table->string('unit')->nullable();
            $table->unsignedInteger('quantity_on_hand')->default(0);
            $table->unsignedInteger('quantity_available')->default(0);
            $table->unsignedInteger('reorder_level')->default(0);
            $table->unsignedInteger('minimum_stock')->default(0);
            $table->boolean('requires_check_in')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['client_identifier', 'type'], 'handyman_inventory_type_idx');
            $table->index(['client_identifier', 'category'], 'handyman_inventory_category_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('handyman_inventory_items');
    }
};

