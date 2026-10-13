<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function makeProperty(User $owner): Property
    {
        return Property::create([
            'user_id' => $owner->id,
            'name' => 'Property '.$owner->id,
            'location' => 'Nairobi',
            'total_units' => 1,
            'status' => 'active',
        ]);
    }

    private function makeUnit(Property $property): Unit
    {
        return Unit::create([
            'property_id' => $property->id,
            'unit_number' => 'A1',
            'rent_amount' => 10000,
            'status' => 'vacant',
        ]);
    }

    public function test_landlord_cannot_update_unit_from_another_property(): void
    {
        $landlord = User::factory()->create(['role' => 'landlord', 'is_active' => true]);
        $other = User::factory()->create(['role' => 'landlord', 'is_active' => true]);

        $ownProperty = $this->makeProperty($landlord);
        $otherProperty = $this->makeProperty($other);
        $otherUnit = $this->makeUnit($otherProperty);

        $response = $this->actingAs($landlord)->put(
            route('landlord.units.update', [$ownProperty, $otherUnit]),
            ['unit_number' => 'HACKED', 'rent_amount' => 1, 'status' => 'vacant']
        );

        $response->assertNotFound();
        $this->assertSame('A1', $otherUnit->fresh()->unit_number);
    }

    public function test_landlord_cannot_delete_unit_from_another_property(): void
    {
        $landlord = User::factory()->create(['role' => 'landlord', 'is_active' => true]);
        $other = User::factory()->create(['role' => 'landlord', 'is_active' => true]);

        $ownProperty = $this->makeProperty($landlord);
        $otherProperty = $this->makeProperty($other);
        $otherUnit = $this->makeUnit($otherProperty);

        $response = $this->actingAs($landlord)->delete(
            route('landlord.units.destroy', [$ownProperty, $otherUnit])
        );

        $response->assertNotFound();
        $this->assertDatabaseHas('units', ['id' => $otherUnit->id]);
    }

    public function test_landlord_can_update_own_unit(): void
    {
        $landlord = User::factory()->create(['role' => 'landlord', 'is_active' => true]);
        $property = $this->makeProperty($landlord);
        $unit = $this->makeUnit($property);

        $response = $this->actingAs($landlord)->put(
            route('landlord.units.update', [$property, $unit]),
            ['unit_number' => 'B2', 'rent_amount' => 12000, 'status' => 'vacant']
        );

        $response->assertRedirect();
        $this->assertSame('B2', $unit->fresh()->unit_number);
    }
}
