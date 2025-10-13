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
        Schema::table('events', function (Blueprint $table) {
            $table->string('lemon_squeezy_product_id')->nullable()->after('status');
            $table->string('lemon_squeezy_variant_id')->nullable()->after('lemon_squeezy_product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['lemon_squeezy_product_id', 'lemon_squeezy_variant_id']);
        });
    }
};
