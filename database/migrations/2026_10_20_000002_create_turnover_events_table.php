<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turnover_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turnover_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role')->nullable();
            $table->string('event');
            $table->text('message')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();

            $table->index('turnover_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turnover_events');
    }
};
