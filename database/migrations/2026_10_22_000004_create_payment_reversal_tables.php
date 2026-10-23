<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_reversals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->decimal('reversed_credit', 12, 2)->default(0);
            $table->text('reason');
            $table->string('status', 20)->default('pending');
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('initiator_name');
            $table->timestamp('initiated_at');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('approver_name')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('rejector_name')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->string('provider_reference')->nullable();
            $table->string('external_refund_status', 24)->default('not_recorded');
            $table->string('external_refund_reference')->nullable();
            $table->timestamp('external_refund_confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['payment_id', 'status'], 'payment_reversals_payment_status_index');
            $table->index(['status', 'initiated_at'], 'payment_reversals_status_date_index');
        });

        Schema::create('payment_reversal_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_reversal_id')->constrained('payment_reversals')->restrictOnDelete();
            $table->foreignId('payment_allocation_id')->constrained('payment_allocations')->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->timestamps();

            $table->unique(['payment_reversal_id', 'payment_allocation_id'], 'payment_reversal_allocation_source_unique');
            $table->index('payment_allocation_id', 'payment_reversal_allocations_source_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_reversal_allocations');
        Schema::dropIfExists('payment_reversals');
    }
};
