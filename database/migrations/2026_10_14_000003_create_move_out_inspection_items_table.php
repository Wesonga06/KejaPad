<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('move_out_inspection_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('move_out_inspection_id')->constrained()->onDelete('cascade');
            $table->string('category');
            $table->string('label');
            $table->string('condition')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index('move_out_inspection_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('move_out_inspection_items');
    }
};
