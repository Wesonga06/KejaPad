<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->decimal('unallocated_amount', 12, 2)->default(0);
            $table->date('payment_date');
            $table->string('reference')->nullable();
            $table->enum('method', ['mpesa', 'cash', 'bank_transfer', 'cheque', 'other'])->default('mpesa');
            $table->enum('source', ['tenant', 'landlord', 'caretaker', 'system'])->default('landlord');
            $table->foreignId('mpesa_payment_id')->nullable()->constrained('mpesa_payments')->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('reference');
            $table->unique('mpesa_payment_id');
            $table->index(['tenant_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
