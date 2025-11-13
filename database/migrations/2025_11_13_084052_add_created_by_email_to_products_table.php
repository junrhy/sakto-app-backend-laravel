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
        Schema::table('products', function (Blueprint $table) {
            $table->string('created_by_email')->nullable()->after('contact_id');
            $table->string('created_by_identifier')->nullable()->after('created_by_email');
            $table->string('created_by_name')->nullable()->after('created_by_identifier');
            $table->index('created_by_email');
            $table->index('created_by_identifier');
            $table->index(['client_identifier', 'created_by_identifier']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['client_identifier', 'created_by_identifier']);
            $table->dropIndex(['created_by_identifier']);
            $table->dropIndex(['created_by_email']);
            $table->dropColumn('created_by_name');
            $table->dropColumn('created_by_identifier');
            $table->dropColumn('created_by_email');
        });
    }
};
