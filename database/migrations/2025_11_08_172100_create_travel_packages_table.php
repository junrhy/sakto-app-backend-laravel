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
        Schema::create('travel_packages', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier')->index();
            $table->string('title');
            $table->string('slug');
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('duration_days')->nullable();
            $table->string('duration_label')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->json('inclusions')->nullable();
            $table->string('package_type')->default('standard');
            $table->string('status')->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->json('media')->nullable();
            $table->timestamps();

            $table->index(['client_identifier', 'slug']);
            $table->index(['client_identifier', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('travel_packages');
    }
};

