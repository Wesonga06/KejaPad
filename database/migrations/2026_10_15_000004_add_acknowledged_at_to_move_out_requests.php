<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('move_out_requests', function (Blueprint $table) {
            $table->timestamp('acknowledged_at')->nullable()->after('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('move_out_requests', function (Blueprint $table) {
            $table->dropColumn('acknowledged_at');
        });
    }
};
