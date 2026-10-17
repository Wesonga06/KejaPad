<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminSystemReportsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->superAdmin()->create();
    }

    public function test_index_renders_with_data(): void
    {
        User::factory()->landlord()->create(['name' => 'Report Landlord']);

        $this->actingAs($this->admin())
            ->get(route('super_admin.reports.index'))
            ->assertOk()
            ->assertSee('System Reports');
    }

    public function test_index_accepts_date_range(): void
    {
        $this->actingAs($this->admin())
            ->get(route('super_admin.reports.index', [
                'from' => now()->subMonth()->toDateString(),
                'to' => now()->toDateString(),
            ]))
            ->assertOk();
    }

    public function test_index_rejects_invalid_range(): void
    {
        $this->actingAs($this->admin())
            ->get(route('super_admin.reports.index', [
                'from' => now()->toDateString(),
                'to' => now()->subMonth()->toDateString(),
            ]))
            ->assertSessionHasErrors('to');
    }

    public function test_users_export_streams_csv(): void
    {
        $user = User::factory()->tenant()->create(['email' => 'exported@example.com']);

        $response = $this->actingAs($this->admin())
            ->get(route('super_admin.reports.export', ['report' => 'users']));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('Email', $content);
        $this->assertStringContainsString('exported@example.com', $content);
    }

    public function test_unknown_report_returns_not_found(): void
    {
        $this->actingAs($this->admin())
            ->get(route('super_admin.reports.export', ['report' => 'nonsense']))
            ->assertNotFound();
    }

    public function test_landlord_cannot_view_system_reports(): void
    {
        $landlord = User::factory()->landlord()->create();

        $this->actingAs($landlord)->get(route('super_admin.reports.index'))->assertForbidden();
    }
}
