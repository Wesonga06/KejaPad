<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminSmsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->superAdmin()->create();
    }

    public function test_index_renders(): void
    {
        $this->actingAs($this->admin())
            ->get(route('super_admin.sms.index'))
            ->assertOk()
            ->assertSee('SMS Settings');
    }

    public function test_update_persists_provider_settings(): void
    {
        $this->actingAs($this->admin())->post(route('super_admin.sms.update'), [
            'provider' => 'log',
            'from' => 'KEJAPAD',
            'workflow_enabled' => '1',
        ])->assertRedirect(route('super_admin.sms.index'));

        $this->assertDatabaseHas('settings', ['key' => 'sms.provider', 'value' => 'log']);
        $this->assertDatabaseHas('settings', ['key' => 'sms.from', 'value' => 'KEJAPAD']);
    }

    public function test_test_message_is_sent_with_log_provider(): void
    {
        app(SettingsService::class)->update(['sms.provider' => 'log']);
        app(SettingsService::class)->applyToConfig();

        $this->actingAs($this->admin())->post(route('super_admin.sms.test'), [
            'recipient' => '0712345678',
            'message' => 'Hello from tests',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('sms_messages', [
            'recipient' => '0712345678',
            'provider' => 'log',
            'status' => 'sent',
        ]);
    }

    public function test_test_message_reports_failure_when_disabled(): void
    {
        $this->actingAs($this->admin())->post(route('super_admin.sms.test'), [
            'recipient' => '0712345678',
            'message' => 'Should not send',
        ])->assertSessionHas('error');

        $this->assertDatabaseHas('sms_messages', [
            'recipient' => '0712345678',
            'status' => 'failed',
        ]);
    }

    public function test_templates_can_be_updated_and_unknown_keys_rejected(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('super_admin.sms.templates.update'), [
            'templates' => ['rent_reminder' => 'Hi {{name}}, pay KES {{amount}} by {{due_date}}.'],
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('sms_templates', [
            'key' => 'rent_reminder',
            'body' => 'Hi {{name}}, pay KES {{amount}} by {{due_date}}.',
        ]);

        $this->actingAs($admin)->post(route('super_admin.sms.templates.update'), [
            'templates' => ['ghost_template' => 'Nope'],
        ])->assertSessionHasErrors('templates.ghost_template');
    }

    public function test_log_page_renders(): void
    {
        $this->actingAs($this->admin())->get(route('super_admin.sms.log'))->assertOk();
    }

    public function test_landlord_approval_triggers_sms_when_provider_configured(): void
    {
        app(SettingsService::class)->update(['sms.provider' => 'log']);
        app(SettingsService::class)->applyToConfig();

        $landlord = User::factory()->pendingLandlord()->create(['phone' => '0700000000']);

        $this->actingAs($this->admin())
            ->post(route('super_admin.landlord-applications.approve', $landlord))
            ->assertRedirect();

        $this->assertDatabaseHas('sms_messages', [
            'recipient' => '0700000000',
            'template' => 'landlord_approved',
            'status' => 'sent',
        ]);
    }

    public function test_workflow_sends_no_sms_when_provider_disabled(): void
    {
        $landlord = User::factory()->pendingLandlord()->create(['phone' => '0700000000']);

        $this->actingAs($this->admin())
            ->post(route('super_admin.landlord-applications.approve', $landlord))
            ->assertRedirect();

        $this->assertDatabaseCount('sms_messages', 0);
    }
}
