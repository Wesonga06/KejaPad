<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppShellTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_role_dashboard_keeps_the_shared_accessible_shell_and_role_navigation(): void
    {
        $dashboards = [
            'super_admin' => ['super_admin.dashboard', 'super_admin.users.index'],
            'landlord' => ['landlord.dashboard', 'landlord.properties.index'],
            'caretaker' => ['caretaker.dashboard', 'caretaker.maintenance.index'],
            'tenant' => ['tenant.dashboard', 'tenant.payments'],
        ];

        foreach ($dashboards as $role => [$dashboardRoute, $roleRoute]) {
            $user = User::factory()->create([
                'role' => $role,
                'is_active' => true,
                'status' => User::STATUS_APPROVED,
            ]);

            $this->actingAs($user)
                ->get(route($dashboardRoute))
                ->assertOk()
                ->assertSee(asset('css/kejapad-design.css'))
                ->assertSee('Primary')
                ->assertSee('sidebar')
                ->assertSee(route($roleRoute));
        }
    }

    public function test_authentication_pages_use_the_shared_brand_stylesheet(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(asset('css/kejapad-design.css'))
            ->assertSee('auth-wrapper');

        $this->get(route('register'))
            ->assertOk()
            ->assertSee(asset('css/kejapad-design.css'))
            ->assertSee('auth-wrapper');
    }
}
