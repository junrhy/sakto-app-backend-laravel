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
        Schema::table('appointments', function (Blueprint $table) {
            // Add VIP priority fields
            $table->boolean('is_priority_patient')->default(false)->after('patient_id');
            $table->integer('priority_level')->default(0)->after('is_priority_patient'); // 0=normal, 1=VIP, 2=emergency
            $table->string('vip_tier')->nullable()->after('priority_level'); // Store VIP tier for reference
            
            // Add indexes for performance
            $table->index(['client_identifier', 'priority_level', 'appointment_date'], 'appointments_priority_date_idx');
            $table->index(['is_priority_patient', 'appointment_date'], 'appointments_vip_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('appointments_priority_date_idx');
            $table->dropIndex('appointments_vip_date_idx');
            
            // Drop columns
            $table->dropColumn(['is_priority_patient', 'priority_level', 'vip_tier']);
        });
    }
};
