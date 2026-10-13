<?php

namespace Tests\Feature;

use App\Models\RentNotice;
use App\Notifications\ArrearsEscalationNotification;
use App\Notifications\RentOverdueNotification;
use App\Services\RentAllocationService;
use App\Services\RentReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\Concerns\CreatesRentScenario;
use Tests\TestCase;

class RentReminderTest extends TestCase
{
    use CreatesRentScenario;
    use RefreshDatabase;

    private function reminders(): RentReminderService
    {
        return app(RentReminderService::class);
    }

    public function test_stage_one_reminder_is_issued_and_notifies_both_parties(): void
    {
        Notification::fake();

        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $unit = $this->unit($property, 10000);
        $user = $this->tenantUser();
        $tenant = $this->tenant($property, $unit, $user);

        $this->rent($tenant, $property, $unit, now()->month, now()->year, 10000, now()->subDays(2)->toDateString());

        $stats = $this->reminders()->run();

        $this->assertSame(1, $stats['reminders']);
        $this->assertDatabaseHas('rent_notices', [
            'tenant_id' => $tenant->id,
            'type' => 'payment_reminder',
            'stage' => 1,
        ]);

        Notification::assertSentTo($user, RentOverdueNotification::class);
        Notification::assertSentTo($landlord, ArrearsEscalationNotification::class);
    }

    public function test_duplicate_reminders_within_cooldown_are_skipped(): void
    {
        Notification::fake();

        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $unit = $this->unit($property, 10000);
        $tenant = $this->tenant($property, $unit, $this->tenantUser());
        $this->rent($tenant, $property, $unit, now()->month, now()->year, 10000, now()->subDays(2)->toDateString());

        $this->reminders()->run();
        $second = $this->reminders()->run();

        $this->assertSame(1, $second['skipped']);
        $this->assertSame(
            1,
            RentNotice::where('tenant_id', $tenant->id)->where('type', 'payment_reminder')->count()
        );
    }

    public function test_escalation_stage_is_reached_for_long_overdue_accounts(): void
    {
        Notification::fake();

        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $unit = $this->unit($property, 10000);
        $tenant = $this->tenant($property, $unit, $this->tenantUser());
        $this->rent($tenant, $property, $unit, now()->month, now()->year, 10000, now()->subDays(25)->toDateString());

        $stats = $this->reminders()->run();

        $this->assertSame(1, $stats['escalations']);
        $this->assertDatabaseHas('rent_notices', [
            'tenant_id' => $tenant->id,
            'type' => 'escalation',
            'stage' => 3,
        ]);
    }

    public function test_notification_preferences_can_suppress_channels(): void
    {
        Notification::fake();
        config()->set('rent.notify_tenant', false);

        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $unit = $this->unit($property, 10000);
        $user = $this->tenantUser();
        $tenant = $this->tenant($property, $unit, $user);
        $this->rent($tenant, $property, $unit, now()->month, now()->year, 10000, now()->subDays(2)->toDateString());

        $this->reminders()->run();

        Notification::assertNotSentTo($user, RentOverdueNotification::class);
        Notification::assertSentTo($landlord, ArrearsEscalationNotification::class);
    }

    public function test_payment_does_not_erase_a_formal_notice_already_issued(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $unit = $this->unit($property, 10000);
        $tenant = $this->tenant($property, $unit, $this->tenantUser());
        $rent = $this->rent($tenant, $property, $unit, now()->month, now()->year, 10000, now()->subDays(20)->toDateString());

        $notice = RentNotice::create([
            'tenant_id' => $tenant->id,
            'rent_record_id' => $rent->id,
            'type' => 'formal_notice',
            'stage' => 1,
            'issued_at' => now()->subDays(2)->toDateString(),
            'recipient' => 'tenant@example.com',
            'delivery_status' => 'sent',
        ]);

        app(RentAllocationService::class)->recordPayment($tenant, 2000, ['payment_date' => now()->toDateString()]);

        $this->assertDatabaseHas('rent_notices', [
            'id' => $notice->id,
            'delivery_status' => 'sent',
        ]);
    }
}
