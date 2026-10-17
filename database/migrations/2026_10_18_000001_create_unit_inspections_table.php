<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_inspections', function (Blueprint $table) {
            $table->id();

            // Stable unit / property references. The unit is never deleted while
            // the passport holds history, so a cascade keeps referential integrity.
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();

            // The tenancy this inspection documents. Kept even after the tenant
            // moves out (nullOnDelete preserves the inspection record).
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();

            // Optional link to the formal move-out inspection workflow so a
            // passport inspection can reference the source record without copying.
            $table->foreignId('move_out_inspection_id')->nullable()->constrained()->nullOnDelete();

            $table->string('type')->default('move_in');   // move_in | routine | move_out
            $table->string('status')->default('draft');   // draft | submitted
            $table->date('inspection_date')->nullable();

            $table->foreignId('inspected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->boolean('keys_returned')->nullable();
            $table->text('keys_notes')->nullable();
            $table->string('electricity_meter')->nullable();
            $table->string('water_meter')->nullable();
            $table->string('cleanliness')->nullable();
            $table->text('general_notes')->nullable();
            $table->json('photos')->nullable();
            $table->string('recommendation')->nullable();

            // Tenant acknowledgement. Acknowledgement confirms receipt only; it is
            // not a waiver of rights or automatic agreement with the findings.
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('acknowledgement_name')->nullable();
            $table->text('acknowledgement_note')->nullable();

            $table->timestamps();

            $table->index(['unit_id', 'type']);
            $table->index(['unit_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_inspections');
    }
};
