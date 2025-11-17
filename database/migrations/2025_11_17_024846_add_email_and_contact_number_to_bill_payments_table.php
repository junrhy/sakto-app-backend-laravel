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
        Schema::table('bill_payments', function (Blueprint $table) {
            $table->string('email')->nullable()->after('notes');
            $table->string('contact_number')->nullable()->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bill_payments', function (Blueprint $table) {
            $table->dropColumn(['email', 'contact_number']);
        });
    }
};
