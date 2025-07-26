<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_review_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('review_id');
            $table->string('reporter_name');
            $table->string('reason');
            $table->text('comment')->nullable();
            $table->string('status')->default('pending'); // pending, reviewed, dismissed
            $table->timestamps();

            $table->foreign('review_id')->references('id')->on('product_reviews')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_review_reports');
    }
}; 