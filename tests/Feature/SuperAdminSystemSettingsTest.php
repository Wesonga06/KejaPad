<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminSystemSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->superAdmin()->create();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'platform_name' => 'KejaPad Test',
            'email_verification' => '0',
            'notice_period_months' => '1',
            'deposit_months' => '1',
            'require_deposit_before_move_in' => '0',
            'invitation_expiry_days' => '7',
            'invitation_single_use' => '1',
            'due_day' => '7',
            'grace_period_days' => '5',
            'allocation_policy' => 'oldest',
            'reminders_enabled' => '1',
            'notify_tenant' => '1',
            'notify_landlord' => '1',
            'alerts_enabled' => '1',
            'alerts_rent_overdue_min_days' => '1',
            'alerts_rent_repeated_periods' => '2',
            'alerts_maintenance_due_soon_days' => '3',
            'alerts_maintenance_recurring_count' => '3',
            'alerts_inspection_interval_days' => '180',
            'alerts_inspection_due_soon_days' => '14',
            'alerts_tenancy_end_soon_days' => '30',
            'alerts_vacancy_days' => '30',
            'alerts_vacancy_critical_days' => '60',
            'alerts_turnover_stalled_days' => '14',
            'alerts_settlement_awaiting_days' => '7',
            'alerts_notifications_enabled' => '1',
            'alerts_notifications_reminder_interval_days' => '7',
        ], $overrides);
    }

    public function test_index_renders(): void
    {
        $this->actingAs($this->admin())
            ->get(route('super_admin.settings.index'))
            ->assertOk()
            ->assertSee('System Configuration');
    }

    public function test_update_persists_audits_and_bridges_config(): void
    {
        $this->actingAs($this->admin())
            ->post(route('super_admin.settings.update'), $this->payload())
            ->assertRedirect(route('super_admin.settings.index'));

        $this->assertDatabaseHas('settings', ['key' => 'app.name', 'value' => 'KejaPad Test']);
        $this->assertDatabaseHas('settings', ['key' => 'rent.due_day', 'value' => '7']);
        $this->assertDatabaseHas('settings', ['key' => 'alerts.vacancy.days', 'value' => '30']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'settings.updated']);

        app(SettingsService::class)->applyToConfig();

        $this->assertSame('KejaPad Test', config('app.name'));
        $this->assertSame(7, config('rent.due_day'));
        $this->assertSame(30, config('alerts.vacancy.days'));
    }

    public function test_update_validates_required_fields(): void
    {
        $this->actingAs($this->admin())
            ->post(route('super_admin.settings.update'), $this->payload(['platform_name' => '']))
            ->assertSessionHasErrors('platform_name');
    }

    public function test_landlord_cannot_manage_system_settings(): void
    {
        $landlord = User::factory()->landlord()->create();

        $this->actingAs($landlord)->get(route('super_admin.settings.index'))->assertForbidden();
        $this->actingAs($landlord)->post(route('super_admin.settings.update'), $this->payload())->assertForbidden();
    }
}
