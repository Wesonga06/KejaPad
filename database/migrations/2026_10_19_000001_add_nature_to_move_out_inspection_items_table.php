<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('move_out_inspection_items', function (Blueprint $table) {
            // Whether an observed condition is possible new damage, a pre-existing
            // defect, or ordinary wear and tear. Never auto-attributed to a tenant.
            $table->string('nature')->nullable()->after('condition');
        });
    }

    public function down(): void
    {
        Schema::table('move_out_inspection_items', function (Blueprint $table) {
            $table->dropColumn('nature');
        });
    }
};
