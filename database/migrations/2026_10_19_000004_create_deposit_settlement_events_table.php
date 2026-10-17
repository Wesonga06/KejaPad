<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposit_settlement_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deposit_settlement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('role')->nullable();
            $table->string('event');
            $table->text('message')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();

            $table->index(['deposit_settlement_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposit_settlement_events');
    }
};
