<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('health_insurance_contributions')) {
            Schema::create('health_insurance_contributions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('member_id')->constrained('health_insurance_members')->onDelete('cascade');
                $table->decimal('amount', 10, 2);
                $table->date('payment_date');
                $table->string('payment_method');
                $table->string('reference_number')->nullable();
                $table->timestamps();

                $table->index('payment_date');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('health_insurance_contributions')) {
            $count = DB::table('health_insurance_contributions')->count();
            if ($count === 0) {
                Schema::dropIfExists('health_insurance_contributions');
            }
        }
    }
}; 