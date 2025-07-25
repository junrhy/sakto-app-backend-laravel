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
        Schema::table('products', function (Blueprint $table) {
            // Supplier related fields
            $table->string('supplier_name')->nullable()->after('metadata');
            $table->string('supplier_email')->nullable()->after('supplier_name');
            $table->string('supplier_phone')->nullable()->after('supplier_email');
            $table->text('supplier_address')->nullable()->after('supplier_phone');
            $table->string('supplier_website')->nullable()->after('supplier_address');
            $table->string('supplier_contact_person')->nullable()->after('supplier_website');
            
            // Purchase related fields
            $table->decimal('purchase_price', 10, 2)->nullable()->after('supplier_contact_person');
            $table->string('purchase_currency', 10)->nullable()->after('purchase_price');
            $table->date('purchase_date')->nullable()->after('purchase_currency');
            $table->string('purchase_order_number')->nullable()->after('purchase_date');
            $table->text('purchase_notes')->nullable()->after('purchase_order_number');
            $table->integer('reorder_point')->nullable()->after('purchase_notes');
            $table->integer('reorder_quantity')->nullable()->after('reorder_point');
            $table->integer('lead_time_days')->nullable()->after('reorder_quantity');
            $table->string('payment_terms')->nullable()->after('lead_time_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop supplier related fields
            $table->dropColumn([
                'supplier_name',
                'supplier_email',
                'supplier_phone',
                'supplier_address',
                'supplier_website',
                'supplier_contact_person',
                'purchase_price',
                'purchase_currency',
                'purchase_date',
                'purchase_order_number',
                'purchase_notes',
                'reorder_point',
                'reorder_quantity',
                'lead_time_days',
                'payment_terms',
            ]);
        });
    }
};
