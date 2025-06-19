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
        if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description');
                $table->decimal('price', 10, 2);
                $table->string('category');
                $table->enum('type', ['physical', 'digital', 'service', 'subscription']);
                $table->string('sku')->nullable();
                $table->integer('stock_quantity')->nullable()->default(0);
                $table->decimal('weight', 8, 2)->nullable();
                $table->string('dimensions')->nullable();
                $table->string('file_url')->nullable();
                $table->string('thumbnail_url')->nullable();
                $table->enum('status', ['draft', 'published', 'archived', 'inactive'])->default('draft');
                $table->json('tags')->nullable();
                $table->json('metadata')->nullable();
                $table->string('client_identifier');
                $table->timestamps();

                // Indexes
                $table->index(['client_identifier', 'type']);
                $table->index(['client_identifier', 'status']);
                $table->index(['client_identifier', 'category']);
                $table->index('sku');
                $table->index('stock_quantity');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('products')) {
            $count = DB::table('products')->count();
            if ($count == 0) {
                Schema::dropIfExists('products');
            }
        }
    }
};
