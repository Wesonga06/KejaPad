<?php

namespace Tests\Feature;

use App\Models\MaintenanceRequest;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaretakerMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    private function landlord(): User
    {
        return User::factory()->create(['role' => 'landlord', 'is_active' => true]);
    }

    private function caretaker(): User
    {
        return User::factory()->create(['role' => 'caretaker', 'is_active' => true]);
    }

    private function property(User $owner): Property
    {
        return Property::create([
            'user_id' => $owner->id,
            'name' => 'Sunrise',
            'location' => 'Nairobi',
            'total_units' => 1,
            'status' => 'active',
        ]);
    }

    private function unit(Property $property): Unit
    {
        return Unit::create([
            'property_id' => $property->id,
            'unit_number' => 'A1',
            'rent_amount' => 10000,
            'status' => 'occupied',
        ]);
    }

    private function tenant(Property $property, Unit $unit): Tenant
    {
        return Tenant::create([
            'property_id' => $property->id,
            'unit_id' => $unit->id,
            'full_name' => 'Jane Doe',
            'phone' => '254712345678',
            'move_in_date' => now()->toDateString(),
            'is_active' => true,
        ]);
    }

    private function maintenance(Property $property, Unit $unit, Tenant $tenant): MaintenanceRequest
    {
        return MaintenanceRequest::create([
            'tenant_id' => $tenant->id,
            'property_id' => $property->id,
            'unit_id' => $unit->id,
            'title' => 'Leaking tap',
            'description' => 'The kitchen tap is leaking.',
            'category' => 'plumbing',
            'priority' => 'high',
            'status' => 'pending',
        ]);
    }

    public function test_caretaker_sees_tenant_request_for_their_property(): void
    {
        $owner = $this->landlord();
        $caretaker = $this->caretaker();
        $property = $this->property($owner);
        $property->caretakers()->attach($caretaker->id);
        $unit = $this->unit($property);
        $tenant = $this->tenant($property, $unit);
        $this->maintenance($property, $unit, $tenant);

        $this->actingAs($caretaker)
            ->get(route('caretaker.maintenance.index'))
            ->assertOk()
            ->assertSee('Leaking tap');
    }

    public function test_caretaker_can_update_status_of_property_request(): void
    {
        $owner = $this->landlord();
        $caretaker = $this->caretaker();
        $property = $this->property($owner);
        $property->caretakers()->attach($caretaker->id);
        $unit = $this->unit($property);
        $tenant = $this->tenant($property, $unit);
        $request = $this->maintenance($property, $unit, $tenant);

        $this->actingAs($caretaker)
            ->get(route('caretaker.maintenance.show', $request))
            ->assertOk();

        $this->actingAs($caretaker)
            ->patch(route('caretaker.maintenance.status', $request), ['status' => 'in_progress'])
            ->assertRedirect();

        $this->assertSame('in_progress', $request->fresh()->status);
    }

    public function test_caretaker_cannot_access_request_for_unrelated_property(): void
    {
        $owner = $this->landlord();
        $caretaker = $this->caretaker();
        $property = $this->property($owner);
        $unit = $this->unit($property);
        $tenant = $this->tenant($property, $unit);
        $request = $this->maintenance($property, $unit, $tenant);

        $this->actingAs($caretaker)
            ->get(route('caretaker.maintenance.show', $request))
            ->assertForbidden();

        $this->actingAs($caretaker)
            ->get(route('caretaker.maintenance.index'))
            ->assertOk()
            ->assertDontSee('Leaking tap');
    }

    public function test_landlord_can_assign_caretakers_to_property(): void
    {
        $owner = $this->landlord();
        $caretaker = $this->caretaker();

        $this->actingAs($owner)
            ->post(route('landlord.properties.store'), [
                'name' => 'Sunrise',
                'location' => 'Nairobi',
                'total_units' => 2,
                'status' => 'active',
                'caretakers' => [$caretaker->id],
            ])
            ->assertRedirect(route('landlord.properties.index'));

        $property = Property::where('name', 'Sunrise')->firstOrFail();

        $this->assertDatabaseHas('caretaker_property', [
            'user_id' => $caretaker->id,
            'property_id' => $property->id,
        ]);
    }

    public function test_landlord_cannot_assign_non_caretaker(): void
    {
        $owner = $this->landlord();
        $other = User::factory()->create(['role' => 'tenant', 'is_active' => true]);

        $this->actingAs($owner)
            ->post(route('landlord.properties.store'), [
                'name' => 'Sunrise',
                'location' => 'Nairobi',
                'total_units' => 2,
                'status' => 'active',
                'caretakers' => [$other->id],
            ])
            ->assertSessionHasErrors('caretakers.0');
    }
}
