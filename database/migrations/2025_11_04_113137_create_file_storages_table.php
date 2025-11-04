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
        Schema::create('file_storages', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier')->index();
            $table->string('name');
            $table->string('original_name');
            $table->string('file_url');
            $table->string('mime_type')->nullable();
            $table->string('file_size')->nullable();
            $table->string('file_type')->nullable(); // image, document, video, audio, other
            $table->text('description')->nullable();
            $table->string('folder')->nullable(); // For organizing files into folders
            $table->json('tags')->nullable(); // For tagging files
            $table->integer('download_count')->default(0);
            $table->timestamps();
            
            $table->index(['client_identifier', 'created_at']);
            $table->index(['client_identifier', 'file_type']);
            $table->index(['client_identifier', 'folder']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_storages');
    }
};
