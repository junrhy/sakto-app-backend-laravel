<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('family_member_edit_requests')) {
            Schema::create('family_member_edit_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('member_id')->constrained('family_members')->onDelete('cascade');
                $table->string('first_name');
                $table->string('last_name');
                $table->date('birth_date');
                $table->date('death_date')->nullable();
                $table->enum('gender', ['male', 'female', 'other']);
                $table->string('photo')->nullable();
                $table->text('notes')->nullable();
                $table->string('client_identifier');
                $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
                $table->timestamps();

                // Add index for faster queries
                $table->index(['client_identifier', 'status']);
                $table->index('member_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('family_member_edit_requests')) {
            $count = DB::table('family_member_edit_requests')->count(); 
            if ($count > 0) {
                Schema::dropIfExists('family_member_edit_requests');
            }
        }
    }
}; 