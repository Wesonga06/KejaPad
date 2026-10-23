<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesMoveOutScenario;
use Tests\TestCase;

class TenantOccupancyTest extends TestCase
{
    use CreatesMoveOutScenario, RefreshDatabase;

    public function test_deactivating_the_only_active_tenant_vacates_the_unit_and_disables_their_account(): void
    {
        $owner = $this->makeLandlord();
        $user = $this->makeTenantUser();
        $property = $this->makeProperty($owner);
        $unit = $this->makeUnit($property, 10000, 'occupied');
        $tenant = $this->makeTenant($property, $unit, $user);

        $this->actingAs($owner)
            ->patch(route('landlord.tenants.deactivate', $tenant))
            ->assertRedirect()
            ->assertSessionHas('success', 'Tenant deactivated and unit marked as vacant.');

        $this->assertFalse($tenant->fresh()->is_active);
        $this->assertSame('vacant', $unit->fresh()->status);
        $this->assertFalse($user->fresh()->is_active);
    }

    public function test_deactivating_one_tenant_does_not_vacate_a_unit_with_another_active_tenancy(): void
    {
        $owner = $this->makeLandlord();
        $outgoingUser = $this->makeTenantUser();
        $remainingUser = $this->makeTenantUser();
        $property = $this->makeProperty($owner);
        $unit = $this->makeUnit($property, 10000, 'occupied');
        $outgoing = $this->makeTenant($property, $unit, $outgoingUser, 'Outgoing Tenant');
        $remaining = $this->makeTenant($property, $unit, $remainingUser, 'Remaining Tenant');

        $this->actingAs($owner)
            ->patch(route('landlord.tenants.deactivate', $outgoing))
            ->assertRedirect()
            ->assertSessionHas('success', 'Tenant deactivated. The unit remains assigned to another active tenancy.');

        $this->assertFalse($outgoing->fresh()->is_active);
        $this->assertTrue($remaining->fresh()->is_active);
        $this->assertSame('occupied', $unit->fresh()->status);
        $this->assertFalse($outgoingUser->fresh()->is_active);
        $this->assertTrue($remainingUser->fresh()->is_active);
        $this->assertSame(1, Tenant::where('unit_id', $unit->id)->where('is_active', true)->count());
    }
}
