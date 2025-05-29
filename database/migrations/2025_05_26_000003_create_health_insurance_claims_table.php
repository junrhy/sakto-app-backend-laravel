<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('health_insurance_claims')) {
            Schema::create('health_insurance_claims', function (Blueprint $table) {
                $table->id();
                $table->foreignId('member_id')->constrained('health_insurance_members')->onDelete('cascade');
                $table->string('claim_type');
                $table->decimal('amount', 10, 2);
                $table->date('date_of_service');
                $table->string('hospital_name');
                $table->text('diagnosis');
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->index('date_of_service');
                $table->index('status');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('health_insurance_claims')) {
            $count = DB::table('health_insurance_claims')->count();
            if ($count === 0) {
                Schema::dropIfExists('health_insurance_claims');
            }
        }
    }
}; 