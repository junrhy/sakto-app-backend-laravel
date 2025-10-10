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
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier')->index(); // REQUIRED for backend API tables
            $table->unsignedBigInteger('chat_conversation_id');
            $table->unsignedBigInteger('sender_id');
            $table->text('content');
            $table->string('message_type')->default('text'); // text, image, file, etc.
            $table->json('metadata')->nullable(); // For additional message data
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('chat_conversation_id')->references('id')->on('chat_conversations')->onDelete('cascade');
            $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade');
            
            // Indexes for performance
            $table->index(['client_identifier', 'chat_conversation_id']);
            $table->index(['client_identifier', 'created_at']);
            $table->index(['chat_conversation_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
