<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // The recorded end date of the tenancy / lease agreement. Optional:
            // when absent the "tenancy ending soon" alert simply cannot fire for
            // that tenancy (the condition is not derivable from other columns).
            $table->date('lease_end_date')->nullable()->after('move_out_date');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('lease_end_date');
        });
    }
};
