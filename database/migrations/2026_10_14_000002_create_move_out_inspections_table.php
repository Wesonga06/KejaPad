<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('move_out_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('move_out_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('caretaker_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('assigned');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->boolean('keys_returned')->nullable();
            $table->text('keys_notes')->nullable();
            $table->string('electricity_meter')->nullable();
            $table->string('water_meter')->nullable();
            $table->string('cleanliness')->nullable();
            $table->text('general_notes')->nullable();
            $table->string('recommendation')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['caretaker_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('move_out_inspections');
    }
};
