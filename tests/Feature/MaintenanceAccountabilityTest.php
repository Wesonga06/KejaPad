<?php

namespace Tests\Feature;

use App\Models\MaintenanceRequest;
use App\Models\MaintenanceRequestEvent;
use App\Models\User;
use App\Notifications\MaintenanceTaskNotification;
use App\Services\CaretakerPerformanceService;
use App\Services\MaintenanceAccountabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\CreatesMoveOutScenario;
use Tests\TestCase;

class MaintenanceAccountabilityTest extends TestCase
{
    use CreatesMoveOutScenario, RefreshDatabase;

    protected function task(User $landlord, User $caretaker, array $overrides = []): MaintenanceRequest
    {
        $property = $this->makeProperty($landlord);
        $property->caretakers()->attach($caretaker->id);
        $unit = $this->makeUnit($property);
        $tenant = $this->makeTenant($property, $unit);

        return MaintenanceRequest::create(array_merge([
            'property_id' => $property->id,
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'title' => 'Leaking tap',
            'description' => 'Water everywhere',
            'category' => 'plumbing',
            'priority' => 'high',
            'status' => 'pending',
        ], $overrides));
    }

    public function test_landlord_can_assign_a_task_and_it_is_logged(): void
    {
        Notification::fake();

        $landlord = $this->makeLandlord();
        $caretaker = $this->makeCaretaker();
        $task = $this->task($landlord, $caretaker);

        $this->actingAs($landlord)
            ->post(route('landlord.maintenance.assign', $task), [
                'assigned_to' => $caretaker->id,
                'due_date' => now()->addDays(3)->toDateString(),
            ])
            ->assertRedirect();

        $task->refresh();

        $this->assertSame($caretaker->id, $task->assigned_to);
        $this->assertSame($landlord->id, $task->assigned_by);
        $this->assertSame('assigned', $task->status);
        $this->assertNotNull($task->assigned_at);
        $this->assertNotNull($task->due_date);

        $this->assertDatabaseHas('maintenance_request_events', [
            'maintenance_request_id' => $task->id,
            'event' => MaintenanceRequestEvent::EVENT_ASSIGNED,
        ]);

        Notification::assertSentTo($caretaker, MaintenanceTaskNotification::class);
    }

    public function test_completion_is_a_claim_until_the_landlord_verifies(): void
    {
        Notification::fake();

        $landlord = $this->makeLandlord();
        $caretaker = $this->makeCaretaker();
        $task = $this->task($landlord, $caretaker);

        app(MaintenanceAccountabilityService::class)->assign($task, $caretaker, $landlord, now()->addDay()->toDateString());

        $this->actingAs($caretaker)
            ->post(route('caretaker.maintenance.complete', $task), [
                'resolution_notes' => 'Replaced the washer.',
            ])
            ->assertRedirect();

        $task->refresh();
        $this->assertSame('resolved', $task->status);
        $this->assertNull($task->verified_at);

        $this->actingAs($landlord)
            ->patch(route('landlord.maintenance.verify', $task), [
                'verification_notes' => 'Looks good.',
            ])
            ->assertRedirect();

        $task->refresh();
        $this->assertSame('closed', $task->status);
        $this->assertNotNull($task->verified_at);
        $this->assertSame($landlord->id, $task->verified_by);

        $this->assertDatabaseHas('maintenance_request_events', [
            'maintenance_request_id' => $task->id,
            'event' => MaintenanceRequestEvent::EVENT_COMPLETION_SUBMITTED,
        ]);
        $this->assertDatabaseHas('maintenance_request_events', [
            'maintenance_request_id' => $task->id,
            'event' => MaintenanceRequestEvent::EVENT_VERIFIED,
        ]);
    }

    public function test_caretaker_cannot_verify_their_own_work(): void
    {
        $landlord = $this->makeLandlord();
        $caretaker = $this->makeCaretaker();
        $task = $this->task($landlord, $caretaker);

        $this->actingAs($caretaker)
            ->patch(route('landlord.maintenance.verify', $task), [])
            ->assertForbidden();
    }

    public function test_landlord_cannot_view_another_landlords_task(): void
    {
        $owner = $this->makeLandlord();
        $other = $this->makeLandlord();
        $caretaker = $this->makeCaretaker();
        $task = $this->task($owner, $caretaker);

        $this->actingAs($other)
            ->get(route('landlord.maintenance.show', $task))
            ->assertForbidden();

        $this->actingAs($other)
            ->patch(route('landlord.maintenance.verify', $task), [])
            ->assertForbidden();
    }

    public function test_returning_for_rework_preserves_the_original_submission(): void
    {
        Notification::fake();

        $landlord = $this->makeLandlord();
        $caretaker = $this->makeCaretaker();
        $task = $this->task($landlord, $caretaker);

        $service = app(MaintenanceAccountabilityService::class);
        $service->assign($task, $caretaker, $landlord, now()->addDay()->toDateString());
        $service->submitCompletion($task->fresh(), $caretaker, ['resolution_notes' => 'First attempt.']);

        $this->actingAs($landlord)
            ->patch(route('landlord.maintenance.return', $task), [
                'return_reason' => 'Still leaking.',
            ])
            ->assertRedirect();

        $task->refresh();
        $this->assertSame('in_progress', $task->status);
        $this->assertNull($task->resolved_at);
        $this->assertNotNull($task->returned_at);
        $this->assertSame('Still leaking.', $task->return_reason);

        // The earlier completion submission must survive the rework.
        $this->assertSame(1, $task->events()->where('event', MaintenanceRequestEvent::EVENT_COMPLETION_SUBMITTED)->count());
        $this->assertSame(1, $task->events()->where('event', MaintenanceRequestEvent::EVENT_RETURNED)->count());
    }

    public function test_overdue_escalation_flags_without_changing_status_or_penalising(): void
    {
        Notification::fake();

        $landlord = $this->makeLandlord();
        $caretaker = $this->makeCaretaker();
        $task = $this->task($landlord, $caretaker);

        app(MaintenanceAccountabilityService::class)
            ->assign($task, $caretaker, $landlord, now()->subDay()->toDateString());

        // An unassigned overdue task must never be escalated.
        $unassigned = $this->task($landlord, $caretaker, [
            'title' => 'Unassigned overdue',
            'due_date' => now()->subWeek()->toDateString(),
        ]);

        $this->artisan('maintenance:escalate-overdue')->assertSuccessful();

        $task->refresh();
        $this->assertNotNull($task->escalated_at);
        $this->assertSame('assigned', $task->status);
        $this->assertSame(1, $task->events()->where('event', MaintenanceRequestEvent::EVENT_ESCALATED)->count());

        $this->assertNull($unassigned->fresh()->escalated_at);

        // Running again must not append duplicate escalation entries.
        $this->artisan('maintenance:escalate-overdue')->assertSuccessful();
        $this->assertSame(1, $task->fresh()->events()->where('event', MaintenanceRequestEvent::EVENT_ESCALATED)->count());
    }

    public function test_evidence_access_is_authorised(): void
    {
        Storage::fake('local');

        $landlord = $this->makeLandlord();
        $caretaker = $this->makeCaretaker();
        $stranger = $this->makeLandlord();
        $task = $this->task($landlord, $caretaker);

        $this->actingAs($caretaker)
            ->post(route('caretaker.maintenance.evidence.store', $task), [
                'evidence' => [UploadedFile::fake()->image('after.jpg')],
            ])
            ->assertRedirect();

        $evidence = $task->fresh()->evidence()->firstOrFail();

        $this->actingAs($landlord)
            ->get(route('maintenance.evidence.show', $evidence))
            ->assertOk();

        $this->actingAs($caretaker)
            ->get(route('maintenance.evidence.show', $evidence))
            ->assertOk();

        $this->actingAs($stranger)
            ->get(route('maintenance.evidence.show', $evidence))
            ->assertForbidden();
    }

    public function test_performance_summary_reports_period_and_sample_size(): void
    {
        Notification::fake();

        $landlord = $this->makeLandlord();
        $caretaker = $this->makeCaretaker();
        $task = $this->task($landlord, $caretaker);

        $service = app(MaintenanceAccountabilityService::class);
        $service->assign($task, $caretaker, $landlord, now()->addDay()->toDateString());
        $service->start($task->fresh(), $caretaker);
        $service->submitCompletion($task->fresh(), $caretaker, ['resolution_notes' => 'Done.']);

        $summary = app(CaretakerPerformanceService::class)->summary($caretaker);

        $this->assertSame(1, $summary['sample_size']);
        $this->assertSame(1, $summary['completed']);
        $this->assertSame(1, $summary['completed_on_time']);
        $this->assertEqualsWithDelta(100.0, $summary['on_time_rate'], 0.01);
        $this->assertSame('All time', $summary['period_label']);
    }

    public function test_caretaker_can_view_their_own_work_history(): void
    {
        $landlord = $this->makeLandlord();
        $caretaker = $this->makeCaretaker();
        $task = $this->task($landlord, $caretaker);

        app(MaintenanceAccountabilityService::class)
            ->assign($task, $caretaker, $landlord, now()->addDay()->toDateString());

        $this->actingAs($caretaker)
            ->get(route('caretaker.history.index'))
            ->assertOk()
            ->assertSee('Leaking tap');
    }

    public function test_landlord_can_view_caretaker_accountability_overview(): void
    {
        $landlord = $this->makeLandlord();
        $caretaker = $this->makeCaretaker();
        $task = $this->task($landlord, $caretaker);

        app(MaintenanceAccountabilityService::class)
            ->assign($task, $caretaker, $landlord, now()->addDay()->toDateString());

        $this->actingAs($landlord)
            ->get(route('landlord.caretakers.index'))
            ->assertOk()
            ->assertSee($caretaker->name);

        $this->actingAs($landlord)
            ->get(route('landlord.caretakers.show', $caretaker))
            ->assertOk();
    }

    public function test_tenant_can_track_the_progress_of_their_request(): void
    {
        Notification::fake();

        $landlord = $this->makeLandlord();
        $caretaker = $this->makeCaretaker();
        $tenantUser = User::factory()->create(['role' => 'tenant', 'is_active' => true]);

        $property = $this->makeProperty($landlord);
        $property->caretakers()->attach($caretaker->id);
        $unit = $this->makeUnit($property);
        $tenant = $this->makeTenant($property, $unit, $tenantUser);

        $task = MaintenanceRequest::create([
            'property_id' => $property->id,
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'title' => 'Broken window',
            'description' => 'Cracked pane in the bedroom',
            'category' => 'general',
            'priority' => 'medium',
            'status' => 'pending',
        ]);

        $service = app(MaintenanceAccountabilityService::class);
        $service->assign($task, $caretaker, $landlord, now()->addDay()->toDateString());
        $service->start($task->fresh(), $caretaker);

        $this->actingAs($tenantUser)
            ->get(route('tenant.maintenance.show', $task))
            ->assertOk()
            ->assertSee('Task assigned')
            ->assertSee('Work started')
            ->assertSee($caretaker->name);

        // A different tenant must never see this request.
        $stranger = User::factory()->create(['role' => 'tenant', 'is_active' => true]);

        $this->actingAs($stranger)
            ->get(route('tenant.maintenance.show', $task))
            ->assertForbidden();
    }

    public function test_landlord_alert_dashboard_surfaces_escalated_maintenance(): void
    {
        Notification::fake();

        $landlord = $this->makeLandlord();
        $caretaker = $this->makeCaretaker();
        $task = $this->task($landlord, $caretaker, ['title' => 'Broken gate']);

        app(MaintenanceAccountabilityService::class)
            ->assign($task, $caretaker, $landlord, now()->subDay()->toDateString());

        $this->artisan('maintenance:escalate-overdue')->assertSuccessful();

        $overview = app(CaretakerPerformanceService::class)->landlordOverview($landlord);
        $this->assertSame(1, $overview['escalated']);

        $this->actingAs($landlord)
            ->get(route('landlord.alerts.index'))
            ->assertOk()
            ->assertSee('Escalated Maintenance')
            ->assertSee('Broken gate');
    }

    public function test_alert_dashboard_hides_escalation_banner_when_nothing_is_overdue(): void
    {
        $landlord = $this->makeLandlord();
        $caretaker = $this->makeCaretaker();
        $this->task($landlord, $caretaker);

        $this->actingAs($landlord)
            ->get(route('landlord.alerts.index'))
            ->assertOk()
            ->assertDontSee('Escalated Maintenance');
    }
}
