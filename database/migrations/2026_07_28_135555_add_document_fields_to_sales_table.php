<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->enum('status', ['completed', 'draft_proforma'])->default('completed')->after('sale_date');
            $table->string('customer_id', 50)->nullable()->after('customer_name');
            $table->string('customer_po_box', 150)->nullable()->after('customer_id');
            $table->string('deliver_to', 255)->nullable()->after('customer_po_box');
            $table->date('delivery_date')->nullable()->after('deliver_to');
            $table->time('delivery_time')->nullable()->after('delivery_date');
            $table->date('validity_date')->nullable()->after('delivery_time');
            $table->string('terms_of_payment', 100)->nullable()->after('validity_date');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'customer_id',
                'customer_po_box',
                'deliver_to',
                'delivery_date',
                'delivery_time',
                'validity_date',
                'terms_of_payment',
            ]);
        });
    }
};
