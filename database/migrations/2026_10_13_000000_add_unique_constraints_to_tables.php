<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rent_records', function (Blueprint $table) {
            $table->unique(['tenant_id', 'month', 'year'], 'rent_records_tenant_month_year_unique');
        });

        Schema::table('units', function (Blueprint $table) {
            $table->unique(['property_id', 'unit_number'], 'units_property_unit_number_unique');
        });

        Schema::table('mpesa_payments', function (Blueprint $table) {
            $table->unique('mpesa_receipt_number', 'mpesa_payments_receipt_unique');
        });
    }

    public function down(): void
    {
        Schema::table('rent_records', function (Blueprint $table) {
            $table->dropUnique('rent_records_tenant_month_year_unique');
        });

        Schema::table('units', function (Blueprint $table) {
            $table->dropUnique('units_property_unit_number_unique');
        });

        Schema::table('mpesa_payments', function (Blueprint $table) {
            $table->dropUnique('mpesa_payments_receipt_unique');
        });
    }
};
