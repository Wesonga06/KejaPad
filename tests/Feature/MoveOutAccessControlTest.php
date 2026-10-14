<?php

namespace Tests\Feature;

use App\Models\MoveOutRequest;
use App\Services\MoveOutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesMoveOutScenario;
use Tests\TestCase;

class MoveOutAccessControlTest extends TestCase
{
    use CreatesMoveOutScenario, RefreshDatabase;

    public function test_tenant_cannot_view_another_tenants_move_out_request(): void
    {
        $owner = $this->makeLandlord();

        $userA = $this->makeTenantUser();
        $propertyA = $this->makeProperty($owner);
        $unitA = $this->makeUnit($propertyA);
        $tenantA = $this->makeTenant($propertyA, $unitA, $userA, 'Alice');
        $moveOut = app(MoveOutService::class)->createRequest($tenantA, [
            'proposed_move_out_date' => now()->addMonthsNoOverflow(1)->toDateString(),
        ], $userA);

        $userB = $this->makeTenantUser();
        $propertyB = $this->makeProperty($owner);
        $unitB = $this->makeUnit($propertyB);
        $this->makeTenant($propertyB, $unitB, $userB, 'Bob');

        $this->actingAs($userB)
            ->get(route('tenant.move-out.show', $moveOut))
            ->assertForbidden();
    }

    public function test_landlord_cannot_view_another_landlords_move_out_request(): void
    {
        $ownerA = $this->makeLandlord();
        $userA = $this->makeTenantUser();
        $propertyA = $this->makeProperty($ownerA);
        $unitA = $this->makeUnit($propertyA);
        $tenantA = $this->makeTenant($propertyA, $unitA, $userA, 'Alice');
        $moveOut = app(MoveOutService::class)->createRequest($tenantA, [
            'proposed_move_out_date' => now()->addMonthsNoOverflow(1)->toDateString(),
        ], $userA);

        $ownerB = $this->makeLandlord();

        $this->actingAs($ownerB)
            ->get(route('landlord.move-outs.show', $moveOut))
            ->assertForbidden();
    }

    public function test_landlord_cannot_manage_another_landlords_deposit(): void
    {
        $ownerA = $this->makeLandlord();
        $propertyA = $this->makeProperty($ownerA);
        $unitA = $this->makeUnit($propertyA);
        $tenantA = $this->makeTenant($propertyA, $unitA);
        $deposit = $this->makeDeposit($tenantA, $propertyA, $unitA, 10000);

        $ownerB = $this->makeLandlord();

        $this->actingAs($ownerB)
            ->get(route('landlord.deposits.show', $deposit))
            ->assertForbidden();

        $this->actingAs($ownerB)
            ->post(route('landlord.deposits.verify', $deposit))
            ->assertForbidden();
    }

    public function test_caretaker_can_view_assigned_inspection_but_not_unrelated_request(): void
    {
        $owner = $this->makeLandlord();
        $user = $this->makeTenantUser();
        $property = $this->makeProperty($owner);
        $unit = $this->makeUnit($property);
        $tenant = $this->makeTenant($property, $unit, $user);
        $moveOut = app(MoveOutService::class)->createRequest($tenant, [
            'proposed_move_out_date' => now()->addMonthsNoOverflow(1)->toDateString(),
        ], $user);

        $assigned = $this->makeCaretaker();
        $unrelated = $this->makeCaretaker();
        $property->caretakers()->attach($assigned->id);

        $this->actingAs($unrelated)
            ->get(route('caretaker.inspections.index'))
            ->assertOk()
            ->assertDontSee($tenant->full_name);

        app(MoveOutService::class)->transition($moveOut, MoveOutRequest::STATUS_APPROVED, $owner);
        $inspection = app(MoveOutService::class)->assignInspection($moveOut, $assigned, null, $owner);

        $this->actingAs($unrelated)
            ->get(route('caretaker.inspections.show', $inspection))
            ->assertForbidden();

        $this->actingAs($assigned)
            ->get(route('caretaker.inspections.show', $inspection))
            ->assertOk()
            ->assertSee($tenant->full_name);
    }
}
