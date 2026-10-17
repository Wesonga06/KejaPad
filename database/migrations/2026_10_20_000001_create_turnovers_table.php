<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turnovers', function (Blueprint $table) {
            $table->id();

            // One coordinated workspace per move-out request.
            $table->foreignId('move_out_request_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();

            // References to the outgoing and incoming tenancies. The tenancy
            // records themselves live in the tenants table; nothing is copied.
            $table->foreignId('outgoing_tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->foreignId('next_tenant_id')->nullable()->constrained('tenants')->nullOnDelete();

            $table->string('status')->default('move_out_planned');

            // Keys and handover information recorded during the turnover.
            $table->text('handover_notes')->nullable();
            $table->timestamp('handover_recorded_at')->nullable();
            $table->foreignId('handover_recorded_by')->nullable()->constrained('users')->nullOnDelete();

            // Authorised readiness confirmation before a unit may be let again.
            $table->text('readiness_notes')->nullable();
            $table->timestamp('readiness_confirmed_at')->nullable();
            $table->foreignId('readiness_confirmed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('new_tenancy_started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->index(['unit_id', 'status']);
            $table->index(['property_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turnovers');
    }
};
