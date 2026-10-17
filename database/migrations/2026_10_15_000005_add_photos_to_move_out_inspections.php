<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('move_out_inspections', function (Blueprint $table) {
            $table->json('photos')->nullable()->after('general_notes');
        });
    }

    public function down(): void
    {
        Schema::table('move_out_inspections', function (Blueprint $table) {
            $table->dropColumn('photos');
        });
    }
};
