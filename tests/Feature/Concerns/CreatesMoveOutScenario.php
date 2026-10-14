<?php

namespace Tests\Feature\Concerns;

use App\Models\Property;
use App\Models\SecurityDeposit;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;

trait CreatesMoveOutScenario
{
    protected function makeLandlord(): User
    {
        return User::factory()->create(['role' => 'landlord', 'is_active' => true]);
    }

    protected function makeTenantUser(): User
    {
        return User::factory()->create(['role' => 'tenant', 'is_active' => true]);
    }

    protected function makeCaretaker(): User
    {
        return User::factory()->create(['role' => 'caretaker', 'is_active' => true]);
    }

    protected function makeProperty(User $owner): Property
    {
        return Property::create([
            'user_id' => $owner->id,
            'name' => 'Prop '.$owner->id,
            'location' => 'Nairobi',
            'total_units' => 1,
            'status' => 'active',
        ]);
    }

    protected function makeUnit(Property $property, float $rent = 10000, string $status = 'occupied'): Unit
    {
        return Unit::create([
            'property_id' => $property->id,
            'unit_number' => 'U'.$property->id,
            'rent_amount' => $rent,
            'status' => $status,
        ]);
    }

    protected function makeTenant(Property $property, Unit $unit, ?User $user = null, string $name = 'Jane Doe'): Tenant
    {
        return Tenant::create([
            'user_id' => $user?->id,
            'property_id' => $property->id,
            'unit_id' => $unit->id,
            'full_name' => $name,
            'phone' => '254712345678',
            'email' => $user?->email,
            'move_in_date' => now()->subMonths(6)->toDateString(),
            'is_active' => true,
        ]);
    }

    protected function makeDeposit(Tenant $tenant, Property $property, Unit $unit, float $required = 10000, float $received = 0): SecurityDeposit
    {
        return SecurityDeposit::create([
            'tenant_id' => $tenant->id,
            'property_id' => $property->id,
            'unit_id' => $unit->id,
            'required_amount' => $required,
            'received_amount' => $received,
            'policy_months' => 1,
            'status' => $received >= $required && $required > 0 ? 'received' : ($received > 0 ? 'partial' : 'pending'),
        ]);
    }
}
