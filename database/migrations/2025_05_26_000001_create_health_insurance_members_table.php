<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('health_insurance_members')) {
            Schema::create('health_insurance_members', function (Blueprint $table) {
                $table->id();
                $table->string('client_identifier');
                $table->string('name');
                $table->date('date_of_birth');
                $table->enum('gender', ['male', 'female', 'other']);
                $table->string('contact_number', 20);
                $table->text('address');
                $table->date('membership_start_date');
                $table->decimal('contribution_amount', 10, 2);
                $table->enum('contribution_frequency', ['monthly', 'quarterly', 'annually']);
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamps();

                $table->index('client_identifier');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('health_insurance_members')) {
            $count = DB::table('health_insurance_members')->count();
            if ($count === 0) {
                Schema::dropIfExists('health_insurance_members');
            }
        }
    }
}; 