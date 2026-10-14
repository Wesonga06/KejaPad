<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('security_deposit_id')->constrained()->onDelete('cascade');
            $table->string('type'); // receipt | deduction | refund
            $table->decimal('amount', 12, 2);
            $table->string('method')->nullable();
            $table->string('reference')->nullable();
            $table->string('status')->default('recorded'); // recorded | verified | proposed | approved | completed | reversed
            $table->text('reason')->nullable();
            $table->text('evidence')->nullable();
            $table->foreignId('move_out_request_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['security_deposit_id', 'type']);
            $table->index('reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposit_transactions');
    }
};
