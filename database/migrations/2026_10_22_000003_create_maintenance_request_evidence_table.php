<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Supporting files attached to a maintenance task (an issue photograph from
 * the tenant, or completion evidence uploaded by the caretaker). Files are held
 * on a private disk and only ever streamed through an authorization check, so
 * they cannot be reached by guessing a public URL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_request_evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('completion');
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('maintenance_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_request_evidence');
    }
};
