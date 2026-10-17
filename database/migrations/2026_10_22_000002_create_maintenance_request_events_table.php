<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The accountability ledger for a maintenance task. Every assignment,
 * reassignment, status change, deadline change, evidence addition, completion
 * submission, verification decision and correction request is appended here.
 * Entries are never edited, so the full history (including earlier completion
 * submissions that were later returned for further work) is preserved even as
 * the task row itself moves on.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_request_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role')->nullable();
            $table->string('event');
            $table->text('message')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();

            $table->index('maintenance_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_request_events');
    }
};
