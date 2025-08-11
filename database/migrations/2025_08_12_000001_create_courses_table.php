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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('slug')->unique();
            $table->string('thumbnail_url')->nullable();
            $table->string('video_url')->nullable();
            $table->enum('difficulty', ['beginner', 'intermediate', 'advanced'])->default('beginner');
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_free')->default(true);
            $table->decimal('price', 10, 2)->nullable();
            $table->string('currency', 3)->default('PHP');
            $table->integer('duration_minutes')->nullable();
            $table->integer('lessons_count')->default(0);
            $table->integer('enrolled_count')->default(0);
            $table->json('tags')->nullable();
            $table->json('requirements')->nullable();
            $table->json('learning_outcomes')->nullable();
            $table->string('instructor_name')->nullable();
            $table->string('instructor_bio')->nullable();
            $table->string('instructor_avatar')->nullable();
            $table->string('category')->nullable();
            $table->string('subcategory')->nullable();
            $table->string('client_identifier');
            $table->timestamps();

            // Indexes
            $table->index(['client_identifier', 'status']);
            $table->index(['client_identifier', 'category']);
            $table->index(['client_identifier', 'is_featured']);
            $table->index('slug');
            $table->index('difficulty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
