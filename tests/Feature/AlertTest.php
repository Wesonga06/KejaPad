<?php

namespace Tests\Feature;

use App\Models\MaintenanceRequest;
use App\Models\Property;
use App\Models\PropertyAlert;
use App\Models\PropertyAlertEvent;
use App\Models\RentRecord;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\PropertyAlertNotification;
use App\Services\AlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Concerns\CreatesMoveOutScenario;
use Tests\TestCase;

class AlertTest extends TestCase
{
    use CreatesMoveOutScenario, RefreshDatabase;

    /**
     * @return array{0: User, 1: Property, 2: Unit, 3: Tenant}
     */
    private function scenario(?User $owner = null): array
    {
        $owner ??= $this->makeLandlord();
        $property = $this->makeProperty($owner);
        $unit = $this->makeUnit($property);
        $tenant = $this->makeTenant($property, $unit);

        return [$owner, $property, $unit, $tenant];
    }

    private function overdueRent(Tenant $tenant, Unit $unit, int $daysAgo, float $due = 10000, float $paid = 0): void
    {
        $dueDate = now()->subDays($daysAgo);

        RentRecord::create([
            'tenant_id' => $tenant->id,
            'property_id' => $tenant->property_id,
            'unit_id' => $unit->id,
            'amount_due' => $due,
            'amount_paid' => $paid,
            'due_date' => $dueDate->toDateString(),
            'month' => (int) $dueDate->month,
            'year' => (int) $dueDate->year,
            'status' => 'pending',
        ]);
    }

    private function alert(string $key): ?PropertyAlert
    {
        return PropertyAlert::where('condition_key', $key)->latest('id')->first();
    }

    // ---------------------------------------------------------------------
    // Detection
    // ---------------------------------------------------------------------

    public function test_rent_overdue_raises_a_warning_alert(): void
    {
        [$owner, $property, $unit, $tenant] = $this->scenario();
        $this->overdueRent($tenant, $unit, 5);

        app(AlertService::class)->run();

        $alert = PropertyAlert::where('type', PropertyAlert::TYPE_RENT_OVERDUE)->first();

        $this->assertNotNull($alert);
        $this->assertSame($property->id, $alert->property_id);
        $this->assertSame($tenant->id, $alert->tenant_id);
        $this->assertSame('warning', $alert->severity);
        $this->assertSame(PropertyAlert::STATUS_ACTIVE, $alert->status);
        $this->assertSame('rent_overdue:tenant:'.$tenant->id, $alert->condition_key);
        $this->assertTrue($alert->events()->where('event', PropertyAlertEvent::EVENT_CREATED)->exists());
    }

    public function test_alert_engine_is_idempotent_across_runs(): void
    {
        [, , $unit, $tenant] = $this->scenario();
        $this->overdueRent($tenant, $unit, 5);

        $service = app(AlertService::class);

        $first = $service->run();
        $second = $service->run();

        $this->assertGreaterThanOrEqual(1, $first['created']);
        $this->assertSame(0, $second['created']);
        $this->assertSame(1, PropertyAlert::where('condition_key', 'rent_overdue:tenant:'.$tenant->id)->count());
    }

    public function test_alert_auto_resolves_when_condition_clears(): void
    {
        [, , $unit, $tenant] = $this->scenario();
        $this->overdueRent($tenant, $unit, 5);

        $service = app(AlertService::class);
        $service->run();

        $this->assertNotNull($this->alert('rent_overdue:tenant:'.$tenant->id));

        // The tenant pays: the underlying condition is gone.
        RentRecord::where('tenant_id', $tenant->id)->update([
            'amount_paid' => 10000,
            'status' => 'paid',
        ]);

        $metrics = $service->run();

        $this->assertSame(PropertyAlert::STATUS_RESOLVED, $this->alert('rent_overdue:tenant:'.$tenant->id)->status);
        $this->assertGreaterThanOrEqual(1, $metrics['resolved']);
    }

    public function test_critical_severity_is_applied_when_deeply_overdue(): void
    {
        [, , $unit, $tenant] = $this->scenario();
        $this->overdueRent($tenant, $unit, 60);

        app(AlertService::class)->run();

        $alert = $this->alert('rent_overdue:tenant:'.$tenant->id);
        $this->assertNotNull($alert);
        $this->assertTrue($alert->isCritical());
    }

    public function test_tenancy_ending_alert_is_raised_for_an_expiring_lease(): void
    {
        [, , , $tenant] = $this->scenario();

        $tenant->update(['lease_end_date' => now()->addDays(10)->toDateString()]);

        app(AlertService::class)->run();

        $this->assertDatabaseHas('property_alerts', [
            'condition_key' => 'tenancy_ending:tenant:'.$tenant->id,
            'type' => PropertyAlert::TYPE_TENANCY_ENDING,
        ]);
    }

    public function test_maintenance_overdue_alert_is_raised(): void
    {
        [, $property, $unit, $tenant] = $this->scenario();

        MaintenanceRequest::create([
            'tenant_id' => $tenant->id,
            'property_id' => $property->id,
            'unit_id' => $unit->id,
            'title' => 'Leaking tap',
            'description' => 'The kitchen tap keeps dripping.',
            'category' => 'plumbing',
            'status' => 'pending',
            'due_date' => now()->subDays(5)->toDateString(),
        ]);

        app(AlertService::class)->run();

        $this->assertDatabaseHas('property_alerts', [
            'type' => PropertyAlert::TYPE_MAINTENANCE_OVERDUE,
            'property_id' => $property->id,
        ]);
    }

    public function test_per_property_override_changes_the_threshold(): void
    {
        // Globally suppress rent alerts...
        config()->set('alerts.rent.overdue_min_days', 999);

        // ...but this property opts back in with its own threshold.
        [$owner, $property, $unit, $tenant] = $this->scenario();
        $property->update(['alert_settings' => ['rent.overdue_min_days' => 1]]);
        $this->overdueRent($tenant, $unit, 5);

        app(AlertService::class)->run();

        $this->assertDatabaseHas('property_alerts', [
            'condition_key' => 'rent_overdue:tenant:'.$tenant->id,
        ]);

        // A second property with no override stays quiet.
        $otherProperty = $this->makeProperty($owner);
        $otherUnit = $this->makeUnit($otherProperty);
        $otherTenant = $this->makeTenant($otherProperty, $otherUnit);
        $this->overdueRent($otherTenant, $otherUnit, 5);

        app(AlertService::class)->run();

        $this->assertDatabaseMissing('property_alerts', [
            'condition_key' => 'rent_overdue:tenant:'.$otherTenant->id,
        ]);
    }

    // ---------------------------------------------------------------------
    // Lifecycle
    // ---------------------------------------------------------------------

    public function test_acknowledge_marks_status_and_records_event(): void
    {
        [$owner, , , $tenant] = $this->scenario();
        $this->overdueRent($tenant, $tenant->unit, 5);

        $service = app(AlertService::class);
        $service->run();

        $alert = PropertyAlert::where('condition_key', 'rent_overdue:tenant:'.$tenant->id)->first();
        $service->acknowledge($alert, $owner, 'Working with the tenant.');

        $alert->refresh();

        $this->assertSame(PropertyAlert::STATUS_ACKNOWLEDGED, $alert->status);
        $this->assertSame($owner->id, $alert->acknowledged_by);
        $this->assertTrue($alert->events()->where('event', PropertyAlertEvent::EVENT_ACKNOWLEDGED)->exists());
    }

    public function test_critical_alert_cannot_be_dismissed(): void
    {
        [$owner, , $unit, $tenant] = $this->scenario();
        $this->overdueRent($tenant, $unit, 90);

        $service = app(AlertService::class);
        $service->run();

        $alert = PropertyAlert::where('condition_key', 'rent_overdue:tenant:'.$tenant->id)->first();
        $this->assertTrue($alert->isCritical());

        $this->expectException(ValidationException::class);

        $service->dismiss($alert, $owner, 'Ignore it');
    }

    public function test_dismiss_records_reason_and_suppresses_without_resolving(): void
    {
        [$owner, , , $tenant] = $this->scenario();
        $this->overdueRent($tenant, $tenant->unit, 5);

        $service = app(AlertService::class);
        $service->run();

        $alert = PropertyAlert::where('condition_key', 'rent_overdue:tenant:'.$tenant->id)->first();
        $service->dismiss($alert, $owner, 'Tenant has agreed a payment plan.');

        $alert->refresh();
        $this->assertSame(PropertyAlert::STATUS_DISMISSED, $alert->status);
        $this->assertSame('Tenant has agreed a payment plan.', $alert->dismissed_reason);

        // Re-running with the condition still present must not raise a duplicate.
        $service->run();
        $this->assertSame(1, PropertyAlert::where('condition_key', $alert->condition_key)->count());
        $this->assertSame(PropertyAlert::STATUS_DISMISSED, $alert->fresh()->status);
    }

    public function test_resolve_is_rejected_while_condition_present(): void
    {
        [$owner, , , $tenant] = $this->scenario();
        $this->overdueRent($tenant, $tenant->unit, 5);

        $service = app(AlertService::class);
        $service->run();

        $alert = PropertyAlert::where('condition_key', 'rent_overdue:tenant:'.$tenant->id)->first();

        $this->expectException(ValidationException::class);
        $service->resolve($alert, $owner);
    }

    public function test_resolve_succeeds_once_condition_is_cleared(): void
    {
        [$owner, , $unit, $tenant] = $this->scenario();
        $this->overdueRent($tenant, $unit, 5);

        $service = app(AlertService::class);
        $service->run();

        $alert = PropertyAlert::where('condition_key', 'rent_overdue:tenant:'.$tenant->id)->first();

        // Clear the condition directly without running the engine.
        RentRecord::where('tenant_id', $tenant->id)->update(['amount_paid' => 10000]);

        $service->resolve($alert, $owner, 'Settled in full.');

        $this->assertSame(PropertyAlert::STATUS_RESOLVED, $alert->fresh()->status);
    }

    // ---------------------------------------------------------------------
    // Notifications
    // ---------------------------------------------------------------------

    public function test_landlord_is_notified_once_and_not_again_the_same_day(): void
    {
        Notification::fake();

        [$owner, , , $tenant] = $this->scenario();
        $this->overdueRent($tenant, $tenant->unit, 5);

        $service = app(AlertService::class);
        $service->run();
        $service->run();

        Notification::assertSentToTimes($owner, PropertyAlertNotification::class, 1);
    }

    // ---------------------------------------------------------------------
    // Permissions and UI
    // ---------------------------------------------------------------------

    public function test_landlord_cannot_view_another_landlords_alert(): void
    {
        [$owner, , , $tenant] = $this->scenario();
        $this->overdueRent($tenant, $tenant->unit, 5);

        app(AlertService::class)->run();

        $alert = PropertyAlert::where('condition_key', 'rent_overdue:tenant:'.$tenant->id)->first();

        $intruder = $this->makeLandlord();

        $this->actingAs($intruder)
            ->get(route('landlord.alerts.show', $alert))
            ->assertForbidden();
    }

    public function test_landlord_can_view_their_alerts_index(): void
    {
        [$owner, , , $tenant] = $this->scenario();
        $this->overdueRent($tenant, $tenant->unit, 5);

        app(AlertService::class)->run();

        $this->actingAs($owner)
            ->get(route('landlord.alerts.index'))
            ->assertOk()
            ->assertSee('Early Warnings');
    }

    public function test_super_admin_can_view_alert_oversight(): void
    {
        [$owner, , , $tenant] = $this->scenario();
        $this->overdueRent($tenant, $tenant->unit, 5);

        app(AlertService::class)->run();

        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin)
            ->get(route('super_admin.alerts.index'))
            ->assertOk()
            ->assertSee('Early-Warning Oversight');
    }

    public function test_alerts_generate_command_reconciles_alerts(): void
    {
        [, , $unit, $tenant] = $this->scenario();
        $this->overdueRent($tenant, $unit, 5);

        $this->artisan('alerts:generate')
            ->assertExitCode(0);

        $this->assertDatabaseHas('property_alerts', [
            'condition_key' => 'rent_overdue:tenant:'.$tenant->id,
        ]);
    }
}
