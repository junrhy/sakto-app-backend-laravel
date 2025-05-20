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
        if (!Schema::hasTable('pages')) {
            Schema::create('pages', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->longText('content');
                $table->string('meta_description')->nullable();
                $table->string('meta_keywords')->nullable();
                $table->boolean('is_published')->default(false);
                $table->string('template')->nullable();
                $table->text('custom_css')->nullable();
                $table->text('custom_js')->nullable();
                $table->string('featured_image')->nullable();
                $table->string('client_identifier');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('pages')) {
            $count = DB::table('pages')->count();
            if ($count === 0) {
                Schema::dropIfExists('pages');
            }
        }
    }
}; 