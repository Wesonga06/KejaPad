<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            // Per-property overrides for early-warning thresholds. Keys mirror
            // the global config('alerts.*') values; anything omitted falls back
            // to the platform default. Never overrides payment/legal rules.
            $table->json('alert_settings')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn('alert_settings');
        });
    }
};
