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
        if (Schema::hasTable('retail_sales')) {
            Schema::table('retail_sales', function (Blueprint $table) {
                if (!Schema::hasColumn('retail_sales', 'discount_id')) {
                    $table->foreignId('discount_id')->nullable()->after('payment_method')->constrained('retail_discounts')->onDelete('set null');
                }
                if (!Schema::hasColumn('retail_sales', 'discount_amount')) {
                    $table->decimal('discount_amount', 10, 2)->default(0)->after('discount_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('retail_sales')) {
            Schema::table('retail_sales', function (Blueprint $table) {
                if (Schema::hasColumn('retail_sales', 'discount_id')) {
                    $table->dropForeign(['discount_id']);
                    $table->dropColumn('discount_id');
                }
                if (Schema::hasColumn('retail_sales', 'discount_amount')) {
                    $table->dropColumn('discount_amount');
                }
            });
        }
    }
};

