<?php

namespace Tests\Feature;

use App\Models\MoveOutRequest;
use App\Services\MoveOutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesMoveOutScenario;
use Tests\TestCase;

class MoveOutPagesRenderTest extends TestCase
{
    use CreatesMoveOutScenario, RefreshDatabase;

    public function test_landlord_move_outs_and_deposits_pages_render(): void
    {
        $owner = $this->makeLandlord();
        $user = $this->makeTenantUser();
        $property = $this->makeProperty($owner);
        $unit = $this->makeUnit($property);
        $tenant = $this->makeTenant($property, $unit, $user);
        $this->makeDeposit($tenant, $property, $unit, 10000);
        $moveOut = app(MoveOutService::class)->createRequest($tenant, [
            'proposed_move_out_date' => now()->addMonthsNoOverflow(1)->toDateString(),
        ], $user);

        $this->actingAs($owner)->get(route('landlord.move-outs.index'))->assertOk()->assertSee('Move-outs');
        $this->actingAs($owner)->get(route('landlord.move-outs.show', $moveOut))->assertOk()->assertSee($tenant->full_name);
        $this->actingAs($owner)->get(route('landlord.deposits.index'))->assertOk()->assertSee('Security Deposits');
        $this->actingAs($owner)->get(route('landlord.deposits.show', $tenant->securityDeposit))->assertOk()->assertSee($tenant->full_name);
    }

    public function test_tenant_move_out_and_deposit_pages_render(): void
    {
        $owner = $this->makeLandlord();
        $user = $this->makeTenantUser();
        $property = $this->makeProperty($owner);
        $unit = $this->makeUnit($property);
        $tenant = $this->makeTenant($property, $unit, $user);

        $this->actingAs($user)->get(route('tenant.move-out.index'))->assertOk()->assertSee('Notice to Vacate');
        $this->actingAs($user)->get(route('tenant.move-out.create'))->assertOk()->assertSee('Submit Notice to Vacate');
        $this->actingAs($user)->get(route('tenant.deposit.show'))->assertOk()->assertSee('My Security Deposit');
    }

    public function test_caretaker_inspections_page_renders(): void
    {
        $owner = $this->makeLandlord();
        $user = $this->makeTenantUser();
        $property = $this->makeProperty($owner);
        $unit = $this->makeUnit($property);
        $tenant = $this->makeTenant($property, $unit, $user);
        $caretaker = $this->makeCaretaker();
        $property->caretakers()->attach($caretaker->id);

        $service = app(MoveOutService::class);
        $moveOut = $service->createRequest($tenant, [
            'proposed_move_out_date' => now()->addMonthsNoOverflow(1)->toDateString(),
        ], $user);
        $service->transition($moveOut, MoveOutRequest::STATUS_APPROVED, $owner);
        $inspection = $service->assignInspection($moveOut, $caretaker, null, $owner);

        $this->actingAs($caretaker)->get(route('caretaker.inspections.index'))->assertOk()->assertSee('Move-out Inspections');
        $this->actingAs($caretaker)->get(route('caretaker.inspections.show', $inspection))->assertOk()->assertSee($tenant->full_name);
    }
}
