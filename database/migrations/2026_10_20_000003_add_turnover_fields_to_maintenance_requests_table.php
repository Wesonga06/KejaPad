<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Turnover repair tasks reuse the existing maintenance task system rather
     * than introducing a second task manager. These additive columns link a
     * repair task to its turnover, give it a due date and let the landlord
     * verify that the completion is satisfactory before the next stage.
     */
    public function up(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->foreignId('turnover_id')->nullable()->after('unit_id')->constrained()->nullOnDelete();
            $table->date('due_date')->nullable()->after('priority');
            $table->timestamp('verified_at')->nullable()->after('resolved_at');
            $table->foreignId('verified_by')->nullable()->after('verified_at')->constrained('users')->nullOnDelete();
            $table->text('verification_notes')->nullable()->after('verified_by');

            $table->index('turnover_id');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('turnover_id');
            $table->dropColumn('due_date');
            $table->dropColumn('verified_at');
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn('verification_notes');
        });
    }
};
