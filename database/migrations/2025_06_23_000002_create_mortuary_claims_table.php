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
        Schema::create('mortuary_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('mortuary_members')->onDelete('cascade');
            $table->enum('claim_type', ['funeral_service', 'burial_plot', 'transportation', 'memorial_service', 'other']);
            $table->decimal('amount', 10, 2);
            $table->date('date_of_death');
            $table->string('deceased_name');
            $table->string('relationship_to_member', 100);
            $table->text('cause_of_death')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('remarks')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('member_id');
            $table->index('claim_type');
            $table->index('status');
            $table->index('date_of_death');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mortuary_claims');
    }
}; 