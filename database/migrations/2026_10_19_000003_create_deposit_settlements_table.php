<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposit_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('move_out_request_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('security_deposit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('draft');

            // Figures are derived from the deposit ledger; these snapshot the
            // statement that was shown to the tenant and the final decision.
            $table->decimal('held_amount', 12, 2)->default(0);
            $table->decimal('proposed_deductions', 12, 2)->default(0);
            $table->decimal('proposed_refund', 12, 2)->default(0);
            $table->decimal('approved_refund', 12, 2)->nullable();
            $table->decimal('refunded_amount', 12, 2)->default(0);
            $table->decimal('retained_amount', 12, 2)->nullable();

            $table->text('landlord_findings')->nullable();
            $table->text('decision_reason')->nullable();
            $table->text('tenant_comment')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('settled_at')->nullable();

            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('settled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposit_settlements');
    }
};
