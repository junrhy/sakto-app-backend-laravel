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
        Schema::table('fnb_orders', function (Blueprint $table) {
            // Add items column if it doesn't exist
            if (!Schema::hasColumn('fnb_orders', 'items')) {
                $table->json('items')->nullable()->after('status');
            }
            
            // Add subtotal and total_amount columns if they don't exist
            if (!Schema::hasColumn('fnb_orders', 'subtotal')) {
                $table->decimal('subtotal', 10, 2)->default(0)->after('items');
            }
            
            if (!Schema::hasColumn('fnb_orders', 'total_amount')) {
                $table->decimal('total_amount', 10, 2)->default(0)->after('subtotal');
            }
            
            // Add discount columns if they don't exist
            if (!Schema::hasColumn('fnb_orders', 'discount')) {
                $table->decimal('discount', 10, 2)->default(0)->after('total_amount');
            }
            
            if (!Schema::hasColumn('fnb_orders', 'discount_type')) {
                $table->enum('discount_type', ['percentage', 'fixed'])->default('percentage')->after('discount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fnb_orders', function (Blueprint $table) {
            $table->dropColumn(['items', 'subtotal', 'total_amount', 'discount', 'discount_type']);
        });
    }
};
