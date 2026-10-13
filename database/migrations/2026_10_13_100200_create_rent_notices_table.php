<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rent_notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('rent_record_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', [
                'payment_reminder',
                'follow_up_reminder',
                'escalation',
                'formal_notice',
                'demand_letter',
                'other',
            ])->default('payment_reminder');
            $table->unsignedTinyInteger('stage')->default(1);
            $table->date('issued_at');
            $table->string('recipient')->nullable();
            $table->enum('delivery_status', [
                'draft',
                'sent',
                'delivered',
                'failed',
                'acknowledged',
            ])->default('sent');
            $table->date('deadline')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'type', 'issued_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rent_notices');
    }
};
