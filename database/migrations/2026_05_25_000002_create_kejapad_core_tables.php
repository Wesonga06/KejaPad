<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landlord_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('address');
            $table->string('city')->default('Nairobi');
            $table->timestamps();
        });

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->unsignedInteger('monthly_rent');
            $table->unsignedTinyInteger('bedrooms')->default(1);
            $table->timestamps();
        });

        Schema::create('tenancies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('users')->cascadeOnDelete();
            $table->date('starts_at');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('rent_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('score')->default(650);
            $table->unsignedSmallInteger('on_time_payments')->default(0);
            $table->json('rewards')->nullable();
            $table->timestamps();
        });

        Schema::create('rent_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenancy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount');
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();
            $table->string('status')->default('pending');
            $table->string('reference')->nullable();
            $table->timestamps();
        });

        Schema::create('maintenance_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('landlord_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('status')->default('received');
            $table->string('priority')->default('normal');
            $table->string('contractor_name')->nullable();
            $table->unsignedTinyInteger('contractor_rating')->nullable();
            $table->unsignedSmallInteger('eta_minutes')->nullable();
            $table->timestamps();
        });

        Schema::create('maintenance_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_ticket_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->text('details');
            $table->timestamp('completed_at')->nullable();
            $table->unsignedTinyInteger('position');
            $table->timestamps();
        });

        Schema::create('rent_pots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->string('month');
            $table->unsignedInteger('total_amount');
            $table->string('status')->default('collecting');
            $table->timestamps();
        });

        Schema::create('rent_pot_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rent_pot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('share_amount');
            $table->unsignedInteger('paid_amount')->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('building_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category');
            $table->text('message');
            $table->string('fingerprint');
            $table->boolean('is_anonymous')->default(true);
            $table->timestamp('flagged_at');
            $table->timestamps();
        });

        Schema::create('escrow_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('landlord_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->unsignedInteger('amount');
            $table->string('status')->default('held');
            $table->timestamp('opened_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('escrow_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('escrow_case_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->text('details');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escrow_events');
        Schema::dropIfExists('escrow_cases');
        Schema::dropIfExists('building_feedback');
        Schema::dropIfExists('rent_pot_members');
        Schema::dropIfExists('rent_pots');
        Schema::dropIfExists('maintenance_updates');
        Schema::dropIfExists('maintenance_tickets');
        Schema::dropIfExists('rent_payments');
        Schema::dropIfExists('rent_scores');
        Schema::dropIfExists('tenancies');
        Schema::dropIfExists('units');
        Schema::dropIfExists('properties');
    }
};
