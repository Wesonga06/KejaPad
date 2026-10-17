<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_condition_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->date('assessed_at')->nullable();

            // A subjective overall assessment, deliberately separate from the
            // factual inspection findings. Nullable so no rating is invented.
            $table->string('rating')->nullable(); // good | attention | poor
            $table->text('summary');

            $table->timestamps();

            $table->index('unit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_condition_assessments');
    }
};
