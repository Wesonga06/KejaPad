<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesRentScenario;
use Tests\TestCase;

class RentPagesRenderTest extends TestCase
{
    use CreatesRentScenario;
    use RefreshDatabase;

    public function test_landlord_dashboard_renders_with_arrears_overview(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $unit = $this->unit($property, 10000);
        $tenant = $this->tenant($property, $unit);
        $this->rent($tenant, $property, $unit, now()->month, now()->year, 10000, now()->subDays(40)->toDateString());

        $this->actingAs($landlord)
            ->get(route('landlord.dashboard'))
            ->assertOk()
            ->assertSee('Arrears Overview');
    }

    public function test_tenant_show_page_renders_ledger_behaviour_and_notices(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $unit = $this->unit($property, 10000);
        $tenant = $this->tenant($property, $unit, $this->tenantUser(), 'Render Me');
        $this->rent($tenant, $property, $unit, now()->month, now()->year, 10000, now()->subDays(10)->toDateString());

        $this->actingAs($landlord)
            ->get(route('landlord.tenants.show', $tenant))
            ->assertOk()
            ->assertSee('Render Me');
    }

    public function test_arrears_overview_page_renders(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $unit = $this->unit($property, 10000);
        $tenant = $this->tenant($property, $unit);
        $this->rent($tenant, $property, $unit, now()->month, now()->year, 10000, now()->subDays(10)->toDateString());

        $this->actingAs($landlord)
            ->get(route('landlord.arrears.index'))
            ->assertOk();
    }

    public function test_tenant_dashboard_and_payments_pages_render(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $unit = $this->unit($property, 10000);
        $user = $this->tenantUser();
        $tenant = $this->tenant($property, $unit, $user);
        $this->rent($tenant, $property, $unit, now()->month, now()->year, 10000, now()->subDays(10)->toDateString());

        $this->actingAs($user)->get(route('tenant.dashboard'))->assertOk();
        $this->actingAs($user)->get(route('tenant.payments'))->assertOk();
    }
}
