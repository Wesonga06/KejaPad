<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_inspection_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_inspection_id')->constrained()->cascadeOnDelete();
            $table->string('category');
            $table->string('label');
            $table->string('condition')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index('unit_inspection_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_inspection_items');
    }
};
