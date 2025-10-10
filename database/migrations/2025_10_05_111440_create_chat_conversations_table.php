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
        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier')->index(); // REQUIRED for backend API tables
            $table->string('title')->nullable(); // Optional conversation title
            $table->string('type')->default('direct'); // direct, group
            $table->json('participants'); // Array of user IDs
            $table->unsignedBigInteger('created_by'); // User who created the conversation
            $table->timestamp('last_message_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            
            // Indexes for performance
            $table->index(['client_identifier', 'created_at']);
            $table->index(['client_identifier', 'last_message_at']);
            $table->index(['client_identifier', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_conversations');
    }
};
