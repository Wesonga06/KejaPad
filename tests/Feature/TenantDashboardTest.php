<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\RentRecord;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function tenantWithRent(string $rentStatus): User
    {
        $landlord = User::factory()->create(['role' => 'landlord', 'is_active' => true]);
        $user = User::factory()->create(['role' => 'tenant', 'is_active' => true]);

        $property = Property::create([
            'user_id' => $landlord->id,
            'name' => 'Sunrise',
            'location' => 'Nairobi',
            'total_units' => 1,
            'status' => 'active',
        ]);

        $unit = Unit::create([
            'property_id' => $property->id,
            'unit_number' => 'A1',
            'rent_amount' => 10000,
            'status' => 'occupied',
        ]);

        $tenant = Tenant::create([
            'user_id' => $user->id,
            'property_id' => $property->id,
            'unit_id' => $unit->id,
            'full_name' => 'Jane Doe',
            'phone' => '254712345678',
            'move_in_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        RentRecord::create([
            'tenant_id' => $tenant->id,
            'property_id' => $property->id,
            'unit_id' => $unit->id,
            'amount_due' => 10000,
            'amount_paid' => $rentStatus === 'paid' ? 10000 : 0,
            'due_date' => now()->toDateString(),
            'status' => $rentStatus,
            'month' => now()->month,
            'year' => now()->year,
        ]);

        return $user;
    }

    public function test_paid_status_shows_on_dashboard_not_sidebar(): void
    {
        $user = $this->tenantWithRent('paid');

        $this->actingAs($user)
            ->get(route('tenant.dashboard'))
            ->assertOk()
            ->assertSee('Rent fully paid')
            ->assertDontSee('Rent fully paid for this month.');
    }

    public function test_overdue_status_shows_on_dashboard_with_pay_action(): void
    {
        $user = $this->tenantWithRent('overdue');

        $this->actingAs($user)
            ->get(route('tenant.dashboard'))
            ->assertOk()
            ->assertSee('Rent overdue')
            ->assertSee('Pay via M-Pesa');
    }
}
