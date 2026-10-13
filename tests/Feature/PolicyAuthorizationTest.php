<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\RentRecord;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function landlord(): User
    {
        return User::factory()->create(['role' => 'landlord', 'is_active' => true]);
    }

    private function property(User $owner): Property
    {
        return Property::create([
            'user_id' => $owner->id,
            'name' => 'Prop '.$owner->id,
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

    private function tenant(Property $property, Unit $unit, ?User $user = null): Tenant
    {
        return Tenant::create([
            'user_id' => $user?->id,
            'property_id' => $property->id,
            'unit_id' => $unit->id,
            'full_name' => 'Tenant '.($user?->id ?? 'x'),
            'phone' => '254712345678',
            'move_in_date' => now()->toDateString(),
            'is_active' => true,
        ]);
    }

    private function rentRecord(Property $property, Unit $unit, Tenant $tenant): RentRecord
    {
        return RentRecord::create([
            'tenant_id' => $tenant->id,
            'property_id' => $property->id,
            'unit_id' => $unit->id,
            'amount_due' => 10000,
            'amount_paid' => 0,
            'due_date' => now()->toDateString(),
            'status' => 'pending',
            'month' => now()->month,
            'year' => now()->year,
        ]);
    }

    public function test_landlord_cannot_edit_another_landlords_property(): void
    {
        $owner = $this->landlord();
        $stranger = $this->landlord();
        $property = $this->property($owner);

        $this->actingAs($stranger)
            ->get(route('landlord.properties.edit', $property))
            ->assertForbidden();
    }

    public function test_landlord_can_edit_own_property(): void
    {
        $owner = $this->landlord();
        $property = $this->property($owner);

        $this->actingAs($owner)
            ->get(route('landlord.properties.edit', $property))
            ->assertOk();
    }

    public function test_landlord_cannot_view_another_landlords_tenant(): void
    {
        $owner = $this->landlord();
        $stranger = $this->landlord();
        $property = $this->property($owner);
        $unit = $this->unit($property);
        $tenant = $this->tenant($property, $unit);

        $this->actingAs($stranger)
            ->get(route('landlord.tenants.show', $tenant))
            ->assertForbidden();
    }

    public function test_landlord_cannot_record_payment_on_another_landlords_rent(): void
    {
        $owner = $this->landlord();
        $stranger = $this->landlord();
        $property = $this->property($owner);
        $unit = $this->unit($property);
        $tenant = $this->tenant($property, $unit);
        $rent = $this->rentRecord($property, $unit, $tenant);

        $this->actingAs($stranger)
            ->post(route('landlord.rent.payment', $rent), [
                'amount_paid' => 5000,
                'payment_date' => now()->toDateString(),
            ])
            ->assertForbidden();
    }

    public function test_tenant_cannot_pay_another_tenants_rent(): void
    {
        $owner = $this->landlord();
        $property = $this->property($owner);
        $unit = $this->unit($property);

        $tenantUser = User::factory()->create(['role' => 'tenant', 'is_active' => true]);
        $otherUser = User::factory()->create(['role' => 'tenant', 'is_active' => true]);
        $this->tenant($property, $unit, $tenantUser);
        $otherTenant = $this->tenant($property, $unit, $otherUser);
        $rent = $this->rentRecord($property, $unit, $otherTenant);

        $this->actingAs($tenantUser)
            ->get(route('tenant.pay', $rent))
            ->assertForbidden();
    }

    public function test_landlord_index_screens_render_with_pagination(): void
    {
        $owner = $this->landlord();
        $property = $this->property($owner);
        $unit = $this->unit($property);
        $this->tenant($property, $unit);

        $this->actingAs($owner)->get(route('landlord.properties.index'))->assertOk();
        $this->actingAs($owner)->get(route('landlord.tenants.index'))->assertOk();
        $this->actingAs($owner)->get(route('landlord.units.index', $property))->assertOk();
    }

    public function test_super_admin_can_list_users(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin)
            ->get(route('super_admin.users.index'))
            ->assertOk();
    }
}
