<?php

namespace Tests\Feature;

use App\Models\MaintenanceRequest;
use App\Models\MoveOutRequest;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Turnover;
use App\Models\TurnoverEvent;
use App\Models\Unit;
use App\Models\UnitInspection;
use App\Models\User;
use App\Services\DepositService;
use App\Services\MoveOutService;
use App\Services\TurnoverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Feature\Concerns\CreatesMoveOutScenario;
use Tests\TestCase;

class TurnoverTest extends TestCase
{
    use CreatesMoveOutScenario, RefreshDatabase;

    /**
     * @return array{0: User, 1: User, 2: Property, 3: Unit, 4: Tenant}
     */
    private function scenario(): array
    {
        $owner = $this->makeLandlord();
        $user = $this->makeTenantUser();
        $property = $this->makeProperty($owner);
        $unit = $this->makeUnit($property);
        $tenant = $this->makeTenant($property, $unit, $user);

        return [$owner, $user, $property, $unit, $tenant];
    }

    private function notice(Tenant $tenant, $actor): MoveOutRequest
    {
        return app(MoveOutService::class)->createRequest($tenant, [
            'proposed_move_out_date' => now()->addMonthsNoOverflow(1)->toDateString(),
        ], $actor);
    }

    // ---------------------------------------------------------------------
    // Opening and cancelling
    // ---------------------------------------------------------------------

    public function test_move_out_notice_opens_a_turnover_workspace(): void
    {
        [, $user, , $unit, $tenant] = $this->scenario();

        $moveOut = $this->notice($tenant, $user);

        $turnover = Turnover::where('move_out_request_id', $moveOut->id)->first();

        $this->assertNotNull($turnover);
        $this->assertSame($unit->id, $turnover->unit_id);
        $this->assertSame($tenant->id, $turnover->outgoing_tenant_id);
        $this->assertSame(Turnover::STATUS_MOVE_OUT_PLANNED, $turnover->status);
        $this->assertTrue($turnover->events()->where('event', TurnoverEvent::EVENT_OPENED)->exists());

        // The unit is not vacated and the tenant is not deactivated at notice.
        $this->assertSame('occupied', $unit->fresh()->status);
        $this->assertTrue($tenant->fresh()->is_active);
    }

    public function test_rejected_move_out_cancels_the_turnover(): void
    {
        [$owner, $user, , , $tenant] = $this->scenario();

        $moveOut = $this->notice($tenant, $user);
        app(MoveOutService::class)->transition($moveOut, MoveOutRequest::STATUS_REJECTED, $owner);

        $turnover = Turnover::where('move_out_request_id', $moveOut->id)->firstOrFail();

        $this->assertSame(Turnover::STATUS_CANCELLED, $turnover->status);
        $this->assertNotNull($turnover->cancelled_at);
        $this->assertTrue($turnover->isClosed());
    }

    // ---------------------------------------------------------------------
    // Inspection and readiness gates
    // ---------------------------------------------------------------------

    public function test_turnover_awaits_inspection_then_review_after_submission(): void
    {
        [$owner, $user, $property, , $tenant] = $this->scenario();
        $caretaker = $this->makeCaretaker();
        $property->caretakers()->attach($caretaker->id);

        $service = app(MoveOutService::class);
        $moveOut = $this->notice($tenant, $user);
        $service->transition($moveOut, MoveOutRequest::STATUS_APPROVED, $owner);
        $service->assignInspection($moveOut, $caretaker, null, $owner);

        $turnover = Turnover::where('move_out_request_id', $moveOut->id)->firstOrFail();
        $this->assertSame(Turnover::STATUS_INSPECTION_PENDING, $turnover->fresh()->status);

        $inspection = $moveOut->inspection()->firstOrFail();
        $service->submitInspection($inspection, ['recommendation' => 'recommend_clearance', 'keys_returned' => 1], $caretaker);

        $this->assertSame(Turnover::STATUS_AWAITING_VERIFICATION, $turnover->fresh()->status);
    }

    public function test_unit_cannot_be_marked_ready_with_open_repairs(): void
    {
        [$owner, $user, $property, $unit, $tenant] = $this->scenario();
        $caretaker = $this->makeCaretaker();
        $property->caretakers()->attach($caretaker->id);

        $service = app(MoveOutService::class);
        $moveOut = $this->notice($tenant, $user);
        $service->transition($moveOut, MoveOutRequest::STATUS_APPROVED, $owner);
        $inspection = $service->assignInspection($moveOut, $caretaker, null, $owner);
        $service->submitInspection($inspection, ['recommendation' => 'recommend_clearance'], $caretaker);
        $service->clear($moveOut->fresh(), $owner, [
            'clearance_decision' => 'cleared',
            'actual_move_out_date' => now()->toDateString(),
        ]);

        $turnover = Turnover::where('move_out_request_id', $moveOut->id)->firstOrFail();
        app(TurnoverService::class)->addTask($turnover, [
            'title' => 'Repaint the living room',
            'category' => 'general',
            'priority' => 'medium',
        ], $owner);

        $this->expectException(HttpException::class);

        app(TurnoverService::class)->markReady($turnover->fresh(), $owner, []);
    }

    public function test_resolved_repair_must_be_verified_before_readiness(): void
    {
        [$owner, $user, $property, , $tenant] = $this->scenario();
        $caretaker = $this->makeCaretaker();
        $property->caretakers()->attach($caretaker->id);

        $service = app(MoveOutService::class);
        $turnover = app(TurnoverService::class);

        $moveOut = $this->notice($tenant, $user);
        $service->transition($moveOut, MoveOutRequest::STATUS_APPROVED, $owner);
        $inspection = $service->assignInspection($moveOut, $caretaker, null, $owner);
        $service->submitInspection($inspection, ['recommendation' => 'recommend_clearance'], $caretaker);
        $service->clear($moveOut->fresh(), $owner, [
            'clearance_decision' => 'cleared',
            'actual_move_out_date' => now()->toDateString(),
        ]);

        $record = Turnover::where('move_out_request_id', $moveOut->id)->firstOrFail();
        $task = $turnover->addTask($record, ['title' => 'Fix the door', 'category' => 'general'], $owner);

        // Marking resolved moves the turnover to "awaiting verification".
        $task->update(['status' => 'resolved', 'resolved_at' => now()]);
        $turnover->syncForMaintenance($task->fresh(), $caretaker);
        $this->assertSame(Turnover::STATUS_AWAITING_VERIFICATION, $record->fresh()->status);

        // Readiness is blocked until the landlord verifies the work.
        try {
            $turnover->markReady($record->fresh(), $owner, []);
            $this->fail('Readiness should have been blocked pending verification.');
        } catch (HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
        }

        $turnover->verifyTask($record->fresh(), $task->fresh(), $owner, ['verification_notes' => 'Looks good']);
        $turnover->markReady($record->fresh(), $owner, []);

        $this->assertSame(Turnover::STATUS_READY_FOR_LETTING, $record->fresh()->status);
    }

    // ---------------------------------------------------------------------
    // End-to-end: notice -> inspection -> repair -> readiness -> new tenancy
    // ---------------------------------------------------------------------

    public function test_full_turnover_through_readiness_and_new_tenancy(): void
    {
        [$owner, $user, $property, $unit, $tenant] = $this->scenario();
        $caretaker = $this->makeCaretaker();
        $property->caretakers()->attach($caretaker->id);

        $service = app(MoveOutService::class);
        $moveOut = $this->notice($tenant, $user);
        $service->transition($moveOut, MoveOutRequest::STATUS_APPROVED, $owner);
        $inspection = $service->assignInspection($moveOut, $caretaker, null, $owner);
        $service->submitInspection($inspection, ['recommendation' => 'recommend_clearance', 'keys_returned' => 1], $caretaker);
        $service->clear($moveOut->fresh(), $owner, [
            'clearance_decision' => 'cleared',
            'actual_move_out_date' => now()->toDateString(),
        ]);

        $turnover = Turnover::where('move_out_request_id', $moveOut->id)->firstOrFail();

        // Landlord adds a repair task through the workspace endpoint.
        $this->actingAs($owner)
            ->post(route('landlord.turnovers.tasks.store', $turnover), [
                'title' => 'Replace broken window pane',
                'description' => 'Bedroom window cracked during move-out.',
                'category' => 'general',
                'priority' => 'high',
                'assigned_to' => $caretaker->id,
            ])
            ->assertRedirect();

        $task = MaintenanceRequest::where('turnover_id', $turnover->id)->firstOrFail();
        $this->assertSame($caretaker->id, $task->assigned_to);
        $this->assertSame('assigned', $task->status);
        $this->assertSame(Turnover::STATUS_REPAIRS_REQUIRED, $turnover->fresh()->status);

        // Caretaker resolves; the turnover waits for verification.
        $this->actingAs($caretaker)
            ->patch(route('caretaker.maintenance.status', $task), [
                'status' => 'resolved',
                'resolution_notes' => 'Replaced the glass',
            ])
            ->assertRedirect();

        $this->assertSame(Turnover::STATUS_AWAITING_VERIFICATION, $turnover->fresh()->status);

        // Landlord verifies the completed repair.
        $this->actingAs($owner)
            ->patch(route('landlord.turnovers.tasks.verify', [$turnover, $task]), [
                'verification_notes' => 'Confirmed.',
            ])
            ->assertRedirect();

        $this->assertNotNull($task->fresh()->verified_at);

        // Confirm readiness, then begin onboarding.
        $this->actingAs($owner)
            ->post(route('landlord.turnovers.ready', $turnover), ['readiness_notes' => 'Turned around'])
            ->assertRedirect();

        $this->assertSame(Turnover::STATUS_READY_FOR_LETTING, $turnover->fresh()->status);

        $this->actingAs($owner)
            ->post(route('landlord.turnovers.onboarding', $turnover))
            ->assertRedirect(route('landlord.tenants.create', [
                'property_id' => $property->id,
                'unit_id' => $unit->id,
            ]));

        // Create the incoming tenant; the unit is reserved pending the deposit.
        $this->actingAs($owner)
            ->post(route('landlord.tenants.store'), [
                'full_name' => 'New Tenant',
                'phone' => '254700000001',
                'property_id' => $property->id,
                'unit_id' => $unit->id,
                'move_in_date' => now()->toDateString(),
            ])
            ->assertRedirect(route('landlord.tenants.index'));

        $incoming = Tenant::where('full_name', 'New Tenant')->firstOrFail();
        $turnover->refresh();

        $this->assertSame($incoming->id, $turnover->next_tenant_id);
        $this->assertSame($tenant->id, $turnover->outgoing_tenant_id);
        $this->assertSame(Turnover::STATUS_NEW_TENANCY_PENDING, $turnover->status);
        $this->assertSame('reserved', $unit->fresh()->status);

        // Verify the deposit; the unit becomes occupied and the turnover closes.
        $deposit = $turnover->nextTenant->securityDeposit;
        $deposits = app(DepositService::class);
        $deposits->recordReceipt($deposit, [
            'amount' => (float) $deposit->required_amount,
            'verify' => true,
        ], $owner);
        $deposits->verifyDeposit($deposit->fresh(), $owner);

        $this->assertSame('occupied', $unit->fresh()->status);
        $this->assertSame(Turnover::STATUS_OCCUPIED, $turnover->fresh()->status);
        $this->assertTrue($turnover->fresh()->isClosed());

        // The outgoing and incoming tenancies remain distinct records.
        $this->assertSame($tenant->id, $turnover->fresh()->outgoingTenant->id);
        $this->assertSame($user->id, $turnover->fresh()->outgoingTenant->user_id);
        $incoming = $turnover->fresh()->nextTenant;
        $this->assertNotSame($tenant->id, $incoming->id);
    }

    public function test_move_in_inspection_can_be_created_for_the_incoming_tenancy(): void
    {
        [$owner, $user, $property, $unit, $tenant] = $this->scenario();
        $caretaker = $this->makeCaretaker();
        $property->caretakers()->attach($caretaker->id);

        $service = app(MoveOutService::class);
        $moveOut = $this->notice($tenant, $user);
        $service->transition($moveOut, MoveOutRequest::STATUS_APPROVED, $owner);
        $inspection = $service->assignInspection($moveOut, $caretaker, null, $owner);
        $service->submitInspection($inspection, ['recommendation' => 'recommend_clearance'], $caretaker);
        $service->clear($moveOut->fresh(), $owner, [
            'clearance_decision' => 'cleared',
            'actual_move_out_date' => now()->toDateString(),
        ]);

        $turnover = Turnover::where('move_out_request_id', $moveOut->id)->firstOrFail();
        app(TurnoverService::class)->markReady($turnover->fresh(), $owner, []);

        $this->actingAs($owner)->post(route('landlord.tenants.store'), [
            'full_name' => 'New Tenant',
            'phone' => '254700000099',
            'property_id' => $property->id,
            'unit_id' => $unit->id,
            'move_in_date' => now()->toDateString(),
        ])->assertRedirect();

        $turnover->refresh();
        $this->actingAs($owner)
            ->post(route('landlord.turnovers.incoming-inspection', $turnover))
            ->assertRedirect();

        $inspection = $turnover->fresh()->incomingMoveInInspection();
        $this->assertNotNull($inspection);
        $this->assertSame(UnitInspection::TYPE_MOVE_IN, $inspection->type);
    }

    // ---------------------------------------------------------------------
    // Access control and isolation
    // ---------------------------------------------------------------------

    public function test_landlord_cannot_view_another_landlords_turnover(): void
    {
        [, $user, , , $tenant] = $this->scenario();
        $moveOut = $this->notice($tenant, $user);
        $turnover = Turnover::where('move_out_request_id', $moveOut->id)->firstOrFail();

        $intruder = $this->makeLandlord();

        $this->actingAs($intruder)
            ->get(route('landlord.turnovers.show', $turnover))
            ->assertForbidden();
    }

    public function test_caretaker_only_sees_turnovers_they_manage(): void
    {
        [$owner, $user, $property, , $tenant] = $this->scenario();
        $caretaker = $this->makeCaretaker();

        // A turnover for a property the caretaker does not manage.
        $moveOut = $this->notice($tenant, $user);
        $turnover = Turnover::where('move_out_request_id', $moveOut->id)->firstOrFail();

        $this->actingAs($caretaker)
            ->get(route('caretaker.turnovers.show', $turnover))
            ->assertForbidden();

        // Once attached to the property, the caretaker can open it.
        $property->caretakers()->attach($caretaker->id);
        $this->actingAs($caretaker)
            ->get(route('caretaker.turnovers.index'))
            ->assertOk();

        $this->actingAs($caretaker)
            ->get(route('caretaker.turnovers.show', $turnover))
            ->assertOk();
    }

    public function test_super_admin_can_view_turnovers(): void
    {
        [, $user, , , $tenant] = $this->scenario();
        $moveOut = $this->notice($tenant, $user);
        $turnover = Turnover::where('move_out_request_id', $moveOut->id)->firstOrFail();

        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin)
            ->get(route('super_admin.turnovers.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('super_admin.turnovers.show', $turnover))
            ->assertOk();
    }

    public function test_landlord_dashboard_shows_turnover_summary(): void
    {
        [$owner, $user, , , $tenant] = $this->scenario();

        $this->notice($tenant, $user);

        $this->actingAs($owner)
            ->get(route('landlord.dashboard'))
            ->assertOk()
            ->assertSee('Turnovers');
    }
}
