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
        Schema::table('patients', function (Blueprint $table) {
            // VIP status and related fields
            $table->boolean('is_vip')->default(false)->after('status');
            $table->enum('vip_tier', ['standard', 'gold', 'platinum', 'diamond'])->default('standard')->after('is_vip');
            $table->timestamp('vip_since')->nullable()->after('vip_tier');
            $table->decimal('vip_discount_percentage', 5, 2)->default(0.00)->after('vip_since'); // e.g., 10.50 for 10.5%
            $table->text('vip_notes')->nullable()->after('vip_discount_percentage');
            $table->boolean('priority_scheduling')->default(false)->after('vip_notes');
            $table->boolean('extended_consultation_time')->default(false)->after('priority_scheduling');
            $table->boolean('dedicated_staff_assignment')->default(false)->after('extended_consultation_time');
            $table->boolean('complimentary_services')->default(false)->after('dedicated_staff_assignment');
            
            // Performance indexes
            $table->index(['client_identifier', 'is_vip']);
            $table->index(['client_identifier', 'vip_tier']);
            $table->index(['client_identifier', 'priority_scheduling']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex(['client_identifier', 'is_vip']);
            $table->dropIndex(['client_identifier', 'vip_tier']);
            $table->dropIndex(['client_identifier', 'priority_scheduling']);
            
            $table->dropColumn([
                'is_vip',
                'vip_tier',
                'vip_since',
                'vip_discount_percentage',
                'vip_notes',
                'priority_scheduling',
                'extended_consultation_time',
                'dedicated_staff_assignment',
                'complimentary_services'
            ]);
        });
    }
};