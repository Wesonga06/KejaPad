<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();

            // The rule that raised the alert and the derived severity/status.
            $table->string('type');
            $table->string('severity');
            $table->string('status')->default('active');

            // A stable key for the underlying condition so the same unresolved
            // issue is never raised twice. It is unique among unresolved alerts.
            $table->string('condition_key');

            $table->string('title');
            $table->text('reason');
            $table->text('recommended_action')->nullable();

            // The record the warning is about (rent record, maintenance request,
            // inspection, turnover, settlement, tenancy, unit ...).
            $table->nullableMorphs('subject');

            $table->json('metadata')->nullable();

            $table->dateTime('detected_at');
            $table->dateTime('last_seen_at');
            $table->dateTime('notified_at')->nullable();

            $table->dateTime('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();

            $table->dateTime('dismissed_at')->nullable();
            $table->foreignId('dismissed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('dismissed_reason')->nullable();

            $table->dateTime('resolved_at')->nullable();

            $table->timestamps();

            $table->index(['property_id', 'status']);
            $table->index(['property_id', 'condition_key']);
            $table->index(['status', 'severity']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_alerts');
    }
};
