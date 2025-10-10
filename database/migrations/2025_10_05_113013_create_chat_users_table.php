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
        Schema::create('chat_users', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier')->index(); // REQUIRED for backend API tables
            $table->string('username')->unique(); // Unique username for chat
            $table->string('email')->unique();
            $table->string('password');
            $table->string('display_name');
            $table->string('avatar_url')->nullable();
            $table->boolean('is_online')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->json('preferences')->nullable(); // Chat preferences (theme, notifications, etc.)
            $table->boolean('is_active')->default(true);
            $table->string('remember_token')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['client_identifier', 'is_active']);
            $table->index(['client_identifier', 'is_online']);
            $table->index(['client_identifier', 'last_seen_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_users');
    }
};
