<?php

namespace Tests\Feature\Concerns;

use App\Models\Property;
use App\Models\RentRecord;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;

trait CreatesRentScenario
{
    protected function landlord(): User
    {
        return User::factory()->create(['role' => 'landlord', 'is_active' => true]);
    }

    protected function tenantUser(): User
    {
        return User::factory()->create(['role' => 'tenant', 'is_active' => true]);
    }

    protected function property(User $owner): Property
    {
        return Property::create([
            'user_id' => $owner->id,
            'name' => 'Prop '.$owner->id,
            'location' => 'Nairobi',
            'total_units' => 1,
            'status' => 'active',
        ]);
    }

    protected function unit(Property $property, float $rent = 10000): Unit
    {
        return Unit::create([
            'property_id' => $property->id,
            'unit_number' => 'U'.$property->id,
            'rent_amount' => $rent,
            'status' => 'occupied',
        ]);
    }

    protected function tenant(Property $property, Unit $unit, ?User $user = null, string $name = 'Jane Doe'): Tenant
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

    protected function rent(
        Tenant $tenant,
        Property $property,
        Unit $unit,
        int $month,
        int $year,
        float $amount = 10000,
        ?string $dueDate = null,
    ): RentRecord {
        return RentRecord::create([
            'tenant_id' => $tenant->id,
            'property_id' => $property->id,
            'unit_id' => $unit->id,
            'amount_due' => $amount,
            'amount_paid' => 0,
            'due_date' => $dueDate ?? sprintf('%04d-%02d-05', $year, $month),
            'status' => 'pending',
            'month' => $month,
            'year' => $year,
        ]);
    }
}
