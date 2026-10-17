<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Caretaker accountability metadata for the existing maintenance task system.
 *
 * These additive columns record who assigned a task and when, when work
 * started, when a task was returned to the caretaker for further work, and
 * when an overdue task was escalated. No existing column is changed, so all
 * historical records remain valid; the task event ledger (a separate table)
 * preserves the full history when a task is reassigned or reopened.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->dateTime('assigned_at')->nullable()->after('assigned_to');
            $table->foreignId('assigned_by')->nullable()->after('assigned_at')->constrained('users')->nullOnDelete();
            $table->dateTime('started_at')->nullable()->after('assigned_by');
            $table->dateTime('escalated_at')->nullable()->after('started_at');

            $table->dateTime('returned_at')->nullable()->after('escalated_at');
            $table->foreignId('returned_by')->nullable()->after('returned_at')->constrained('users')->nullOnDelete();
            $table->text('return_reason')->nullable()->after('returned_by');

            $table->index(['assigned_to', 'status']);
            $table->index(['due_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->dropIndex(['assigned_to', 'status']);
            $table->dropIndex(['due_date', 'status']);
            $table->dropConstrainedForeignId('assigned_by');
            $table->dropConstrainedForeignId('returned_by');
            $table->dropColumn([
                'assigned_at',
                'started_at',
                'escalated_at',
                'returned_at',
                'return_reason',
            ]);
        });
    }
};
