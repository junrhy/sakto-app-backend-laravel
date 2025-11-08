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
        Schema::create('handyman_work_order_attachments', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier')->index();
            $table->foreignId('work_order_id')->constrained('handyman_work_orders')->onDelete('cascade');
            $table->string('file_path');
            $table->string('file_type')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['client_identifier', 'file_type'], 'handyman_wo_attach_file_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('handyman_work_order_attachments');
    }
};

