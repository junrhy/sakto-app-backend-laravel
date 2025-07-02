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
        Schema::table('product_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('contact_id')->nullable()->after('client_identifier');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('set null');
            $table->index('contact_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_orders', function (Blueprint $table) {
            $table->dropForeign(['contact_id']);
            $table->dropIndex(['contact_id']);
            $table->dropColumn('contact_id');
        });
    }
};
