<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_alert_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_alert_id')->constrained('property_alerts')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event');
            $table->text('message')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();

            $table->index('property_alert_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_alert_events');
    }
};
