<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('title');
            $table->string('type')->default('other');
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();

            // landlord_only: visible to the landlord (and caretakers). tenant_visible:
            // additionally shared with the tenant it relates to.
            $table->string('visibility')->default('landlord_only');

            $table->nullableMorphs('related'); // optional link to the source record
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['unit_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_documents');
    }
};
