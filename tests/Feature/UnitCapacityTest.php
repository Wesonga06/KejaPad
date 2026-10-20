<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitCapacityTest extends TestCase
{
    use RefreshDatabase;

    private function landlord(): User
    {
        return User::factory()->create([
            'role' => 'landlord',
            'status' => User::STATUS_APPROVED,
            'is_active' => true,
        ]);
    }

    private function property(User $landlord, int $capacity = 10): Property
    {
        return Property::create([
            'user_id' => $landlord->id,
            'name' => 'Capacity House',
            'location' => 'Nairobi',
            'total_units' => $capacity,
            'status' => 'active',
        ]);
    }

    private function unit(Property $property, int $number, string $status = 'vacant'): Unit
    {
        return Unit::create([
            'property_id' => $property->id,
            'unit_number' => 'U'.$number,
            'rent_amount' => 10000,
            'status' => $status,
        ]);
    }

    private function unitPayload(string $number = 'NEW'): array
    {
        return [
            'unit_number' => $number,
            'rent_amount' => 10000,
            'status' => 'vacant',
        ];
    }

    public function test_property_with_eight_of_ten_units_accepts_another_unit(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord);
        for ($number = 1; $number <= 8; $number++) {
            $this->unit($property, $number);
        }

        $this->actingAs($landlord)
            ->post(route('landlord.units.store', $property), $this->unitPayload())
            ->assertRedirect(route('landlord.units.index', $property));

        $this->assertSame(9, $property->units()->count());
    }

    public function test_property_with_nine_of_ten_units_accepts_its_final_unit(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord);
        for ($number = 1; $number <= 9; $number++) {
            $this->unit($property, $number);
        }

        $this->actingAs($landlord)
            ->post(route('landlord.units.store', $property), $this->unitPayload())
            ->assertRedirect(route('landlord.units.index', $property));

        $this->assertSame(10, $property->units()->count());
    }

    public function test_property_at_capacity_cannot_accept_an_eleventh_unit(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord);
        for ($number = 1; $number <= 10; $number++) {
            $this->unit($property, $number);
        }

        $this->actingAs($landlord)
            ->from(route('landlord.units.create', $property))
            ->post(route('landlord.units.store', $property), $this->unitPayload())
            ->assertSessionHasErrors('capacity')
            ->assertSessionHasErrors(['capacity' => 'You have reached the maximum number of units listed for this property. Your property allows 10 units, and 10 have already been registered.']);

        $this->assertSame(10, $property->units()->count());
        $this->assertDatabaseMissing('units', ['property_id' => $property->id, 'unit_number' => 'NEW']);
    }

    public function test_vacant_units_count_toward_capacity(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord, 1);
        $this->unit($property, 1, 'vacant');

        $this->actingAs($landlord)
            ->post(route('landlord.units.store', $property), $this->unitPayload())
            ->assertSessionHasErrors('capacity');

        $this->assertSame(1, $property->units()->count());
    }

    public function test_repeated_backend_requests_cannot_use_the_same_final_capacity_slot_twice(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord, 1);

        $this->actingAs($landlord)
            ->post(route('landlord.units.store', $property), $this->unitPayload('FIRST'))
            ->assertRedirect(route('landlord.units.index', $property));

        $this->post(route('landlord.units.store', $property), $this->unitPayload('SECOND'))
            ->assertSessionHasErrors('capacity');

        $this->assertSame(1, $property->units()->count());
    }

    public function test_submission_is_rejected_if_capacity_fills_after_the_form_was_opened(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord, 10);
        for ($number = 1; $number <= 9; $number++) {
            $this->unit($property, $number);
        }

        $this->actingAs($landlord)
            ->get(route('landlord.units.create', $property))
            ->assertOk()
            ->assertSee('Registered units: 9 of 10');

        $this->unit($property, 10);

        $this->post(route('landlord.units.store', $property), $this->unitPayload())
            ->assertSessionHasErrors('capacity');

        $this->assertSame(10, $property->units()->count());
    }

    public function test_landlord_cannot_add_units_to_a_property_they_do_not_own(): void
    {
        $owner = $this->landlord();
        $otherLandlord = $this->landlord();
        $property = $this->property($owner, 10);

        $this->actingAs($otherLandlord)
            ->post(route('landlord.units.store', $property), $this->unitPayload())
            ->assertForbidden();

        $this->assertSame(0, $property->units()->count());
    }

    public function test_submitted_property_id_cannot_assign_a_unit_to_another_property(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord, 10);
        $otherProperty = $this->property($landlord, 10);

        $this->actingAs($landlord)
            ->post(route('landlord.units.store', $property), $this->unitPayload() + ['property_id' => $otherProperty->id])
            ->assertRedirect(route('landlord.units.index', $property));

        $this->assertDatabaseHas('units', ['property_id' => $property->id, 'unit_number' => 'NEW']);
        $this->assertDatabaseMissing('units', ['property_id' => $otherProperty->id, 'unit_number' => 'NEW']);
    }

    public function test_property_capacity_cannot_be_reduced_below_registered_units(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord, 10);
        $this->unit($property, 1);
        $this->unit($property, 2);

        $this->actingAs($landlord)
            ->put(route('landlord.properties.update', $property), [
                'name' => $property->name,
                'location' => $property->location,
                'description' => $property->description,
                'total_units' => 1,
                'status' => $property->status,
            ])
            ->assertSessionHasErrors('total_units');

        $this->assertSame(10, $property->fresh()->total_units);
        $this->assertSame(2, $property->units()->count());
    }

    public function test_existing_over_capacity_records_are_preserved_and_block_additional_units(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord, 1);
        $this->unit($property, 1);
        $this->unit($property, 2);

        $this->actingAs($landlord)
            ->post(route('landlord.units.store', $property), $this->unitPayload('THIRD'))
            ->assertSessionHasErrors('capacity');

        $this->assertSame(1, $property->fresh()->total_units);
        $this->assertSame(2, $property->units()->count());
        $this->assertDatabaseHas('units', ['property_id' => $property->id, 'unit_number' => 'U1']);
        $this->assertDatabaseHas('units', ['property_id' => $property->id, 'unit_number' => 'U2']);
    }

    public function test_capacity_is_shown_on_property_units_and_add_unit_pages(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord, 10);
        for ($number = 1; $number <= 8; $number++) {
            $this->unit($property, $number);
        }

        $this->actingAs($landlord)
            ->get(route('landlord.units.index', $property))
            ->assertOk()
            ->assertSee('Registered units: 8 of 10')
            ->assertSee('2 units remaining');

        $this->get(route('landlord.units.create', $property))
            ->assertOk()
            ->assertSee('Registered units: 8 of 10')
            ->assertSee('2 units remaining');
    }
}
