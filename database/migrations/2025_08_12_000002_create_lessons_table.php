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
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('content')->nullable();
            $table->string('video_url')->nullable();
            $table->string('audio_url')->nullable();
            $table->string('file_url')->nullable();
            $table->enum('type', ['video', 'audio', 'text', 'quiz', 'assignment'])->default('video');
            $table->integer('duration_minutes')->nullable();
            $table->integer('order_index');
            $table->boolean('is_free_preview')->default(false);
            $table->json('attachments')->nullable();
            $table->json('quiz_data')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['course_id', 'order_index']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
