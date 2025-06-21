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
            $table->boolean('is_paid_event')->default(false)->after('is_public');
            $table->decimal('event_price', 10, 2)->nullable()->after('is_paid_event');
            $table->string('currency', 3)->default('PHP')->after('event_price');
            $table->text('payment_instructions')->nullable()->after('currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['is_paid_event', 'event_price', 'currency', 'payment_instructions']);
        });
    }
}; 