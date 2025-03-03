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
        if (!Schema::hasTable('family_relationships')) {
            Schema::create('family_relationships', function (Blueprint $table) {
                $table->id();
                $table->foreignId('from_member_id')->constrained('family_members')->onDelete('cascade');
                $table->foreignId('to_member_id')->constrained('family_members')->onDelete('cascade');
                $table->enum('relationship_type', ['parent', 'child', 'spouse', 'sibling']);
                $table->timestamps();

                $table->unique(
                    ['from_member_id', 'to_member_id', 'relationship_type'],
                    'family_rel_unique'
                );
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('family_relationships')) {
            $count = DB::table('family_relationships')->count();
            if ($count === 0) {
                Schema::dropIfExists('family_relationships');
            }
        }
    }
};
