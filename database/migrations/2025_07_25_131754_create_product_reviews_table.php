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
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('reviewer_name');
            $table->string('reviewer_email');
            $table->string('title')->nullable();
            $table->text('content');
            $table->integer('rating')->comment('1-5 star rating');
            $table->boolean('is_verified_purchase')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->json('images')->nullable()->comment('Array of review image URLs');
            $table->json('helpful_votes')->nullable()->comment('Array of user IDs who found this helpful');
            $table->json('unhelpful_votes')->nullable()->comment('Array of user IDs who found this unhelpful');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('featured_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['product_id', 'is_approved']);
            $table->index(['product_id', 'rating']);
            $table->index(['reviewer_email', 'is_approved']);
            $table->index(['is_featured', 'is_approved']);
            $table->unique(['product_id', 'reviewer_email'], 'unique_product_reviewer_review');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
    }
};
