<?php

namespace Tests\Feature;

use App\Models\MoveOutInspection;
use App\Models\MoveOutRequest;
use App\Services\MoveOutService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Feature\Concerns\CreatesMoveOutScenario;
use Tests\TestCase;

class MoveOutWorkflowTest extends TestCase
{
    use CreatesMoveOutScenario, RefreshDatabase;

    private function scenario(): array
    {
        $owner = $this->makeLandlord();
        $user = $this->makeTenantUser();
        $property = $this->makeProperty($owner);
        $unit = $this->makeUnit($property);
        $tenant = $this->makeTenant($property, $unit, $user);

        return [$owner, $user, $property, $unit, $tenant];
    }

    public function test_earliest_permitted_date_uses_calendar_months(): void
    {
        $service = app(MoveOutService::class);

        $earliest = $service->earliestPermittedDate(Carbon::parse('2026-01-31'), 1);

        $this->assertSame('2026-02-28', $earliest->toDateString());
    }

    public function test_tenant_can_submit_notice_with_valid_date(): void
    {
        [$owner, $user, $property, $unit, $tenant] = $this->scenario();

        $this->actingAs($user)
            ->post(route('tenant.move-out.store'), [
                'proposed_move_out_date' => now()->addMonthsNoOverflow(1)->toDateString(),
                'reason' => 'Relocating',
                'acknowledged' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('move_out_requests', [
            'tenant_id' => $tenant->id,
            'status' => MoveOutRequest::STATUS_SUBMITTED,
        ]);

        $this->assertNotNull(MoveOutRequest::where('tenant_id', $tenant->id)->first()->acknowledged_at);
    }

    public function test_notice_requires_acknowledgement(): void
    {
        [$owner, $user, $property, $unit, $tenant] = $this->scenario();

        $this->actingAs($user)
            ->post(route('tenant.move-out.store'), [
                'proposed_move_out_date' => now()->addMonthsNoOverflow(1)->toDateString(),
                'reason' => 'Relocating',
            ])
            ->assertSessionHasErrors('acknowledged');

        $this->assertDatabaseCount('move_out_requests', 0);
    }

    public function test_tenant_cannot_submit_notice_that_breaches_notice_period(): void
    {
        [$owner, $user, $property, $unit, $tenant] = $this->scenario();

        $this->actingAs($user)
            ->post(route('tenant.move-out.store'), [
                'proposed_move_out_date' => now()->addDays(3)->toDateString(),
                'acknowledged' => '1',
            ])
            ->assertSessionHasErrors('proposed_move_out_date');

        $this->assertDatabaseCount('move_out_requests', 0);
    }

    public function test_tenant_cannot_submit_duplicate_open_request(): void
    {
        [$owner, $user, $property, $unit, $tenant] = $this->scenario();

        app(MoveOutService::class)->createRequest($tenant, [
            'proposed_move_out_date' => now()->addMonthsNoOverflow(1)->toDateString(),
        ], $user);

        $this->actingAs($user)
            ->post(route('tenant.move-out.store'), [
                'proposed_move_out_date' => now()->addMonthsNoOverflow(2)->toDateString(),
                'acknowledged' => '1',
            ])
            ->assertSessionHasErrors('proposed_move_out_date');

        $this->assertDatabaseCount('move_out_requests', 1);
    }

    public function test_landlord_can_approve_request(): void
    {
        [$owner, $user, $property, $unit, $tenant] = $this->scenario();
        $moveOut = app(MoveOutService::class)->createRequest($tenant, [
            'proposed_move_out_date' => now()->addMonthsNoOverflow(1)->toDateString(),
        ], $user);

        $this->actingAs($owner)
            ->patch(route('landlord.move-outs.review', $moveOut), ['status' => MoveOutRequest::STATUS_APPROVED])
            ->assertRedirect();

        $this->assertSame(MoveOutRequest::STATUS_APPROVED, $moveOut->fresh()->status);
        $this->assertNotNull($moveOut->fresh()->reviewed_at);
    }

    public function test_clearance_is_rejected_before_approval(): void
    {
        [$owner, $user, $property, $unit, $tenant] = $this->scenario();
        $moveOut = app(MoveOutService::class)->createRequest($tenant, [
            'proposed_move_out_date' => now()->addMonthsNoOverflow(1)->toDateString(),
        ], $user);

        $this->actingAs($owner)
            ->patch(route('landlord.move-outs.clear', $moveOut), [
                'clearance_decision' => 'cleared',
                'actual_move_out_date' => now()->addMonthsNoOverflow(1)->toDateString(),
            ])
            ->assertSessionHasErrors('clearance_decision');

        $this->assertSame(MoveOutRequest::STATUS_SUBMITTED, $moveOut->fresh()->status);
    }

    public function test_assigning_inspection_seeds_checklist_and_advances_status(): void
    {
        [$owner, $user, $property, $unit, $tenant] = $this->scenario();
        $caretaker = $this->makeCaretaker();
        $property->caretakers()->attach($caretaker->id);

        $service = app(MoveOutService::class);
        $moveOut = $service->createRequest($tenant, [
            'proposed_move_out_date' => now()->addMonthsNoOverflow(1)->toDateString(),
        ], $user);
        $service->transition($moveOut, MoveOutRequest::STATUS_APPROVED, $owner);

        $this->actingAs($owner)
            ->post(route('landlord.move-outs.inspection.assign', $moveOut), ['caretaker_id' => $caretaker->id])
            ->assertRedirect();

        $inspection = MoveOutInspection::where('move_out_request_id', $moveOut->id)->firstOrFail();
        $this->assertSame($caretaker->id, $inspection->caretaker_id);
        $expectedItems = collect(MoveOutInspection::checklist())->sum(fn ($group) => count($group['items']));
        $this->assertSame($expectedItems, $inspection->items()->count());
        $this->assertSame(MoveOutRequest::STATUS_INSPECTION_SCHEDULED, $moveOut->fresh()->status);
    }

    public function test_caretaker_submits_inspection_and_request_advances(): void
    {
        [$owner, $user, $property, $unit, $tenant] = $this->scenario();
        $caretaker = $this->makeCaretaker();
        $property->caretakers()->attach($caretaker->id);

        $service = app(MoveOutService::class);
        $moveOut = $service->createRequest($tenant, [
            'proposed_move_out_date' => now()->addMonthsNoOverflow(1)->toDateString(),
        ], $user);
        $service->transition($moveOut, MoveOutRequest::STATUS_APPROVED, $owner);
        $inspection = $service->assignInspection($moveOut, $caretaker, null, $owner);

        $this->actingAs($caretaker)
            ->post(route('caretaker.inspections.submit', $inspection), [
                'recommendation' => 'recommend_clearance',
                'keys_returned' => 1,
                'cleanliness' => 'clean',
            ])
            ->assertRedirect();

        $this->assertSame(MoveOutInspection::STATUS_SUBMITTED, $inspection->fresh()->status);
        $this->assertSame(MoveOutRequest::STATUS_INSPECTION_COMPLETED, $moveOut->fresh()->status);
    }

    public function test_inspection_can_store_photos(): void
    {
        [$owner, $user, $property, $unit, $tenant] = $this->scenario();
        $caretaker = $this->makeCaretaker();
        $property->caretakers()->attach($caretaker->id);

        $service = app(MoveOutService::class);
        $moveOut = $service->createRequest($tenant, [
            'proposed_move_out_date' => now()->addMonthsNoOverflow(1)->toDateString(),
        ], $user);
        $service->transition($moveOut, MoveOutRequest::STATUS_APPROVED, $owner);
        $inspection = $service->assignInspection($moveOut, $caretaker, null, $owner);

        $this->actingAs($caretaker)
            ->post(route('caretaker.inspections.submit', $inspection), [
                'recommendation' => 'recommend_clearance',
                'keys_returned' => 1,
                'cleanliness' => 'clean',
                'photos' => [
                    UploadedFile::fake()->image('room1.jpg'),
                    UploadedFile::fake()->image('room2.jpg'),
                ],
            ])
            ->assertRedirect();

        $inspection->refresh();
        $this->assertNotNull($inspection->photos);
        $this->assertCount(2, $inspection->photos);
    }

    public function test_clearance_closes_tenancy_and_vacates_unit(): void
    {
        [$owner, $user, $property, $unit, $tenant] = $this->scenario();
        $service = app(MoveOutService::class);
        $moveOut = $service->createRequest($tenant, [
            'proposed_move_out_date' => now()->addMonthsNoOverflow(1)->toDateString(),
        ], $user);
        $service->transition($moveOut, MoveOutRequest::STATUS_APPROVED, $owner);

        $this->actingAs($owner)
            ->patch(route('landlord.move-outs.clear', $moveOut), [
                'clearance_decision' => 'cleared',
                'actual_move_out_date' => now()->addMonthsNoOverflow(1)->toDateString(),
            ])
            ->assertRedirect();

        $this->assertSame(MoveOutRequest::STATUS_COMPLETED, $moveOut->fresh()->status);
        $this->assertFalse($tenant->fresh()->is_active);
        $this->assertSame('vacant', $unit->fresh()->status);
    }
}
