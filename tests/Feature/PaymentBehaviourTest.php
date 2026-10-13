<?php

namespace Tests\Feature;

use App\Services\PaymentBehaviourService;
use App\Services\RentAllocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesRentScenario;
use Tests\TestCase;

class PaymentBehaviourTest extends TestCase
{
    use CreatesRentScenario;
    use RefreshDatabase;

    private function allocation(): RentAllocationService
    {
        return app(RentAllocationService::class);
    }

    private function behaviour(): PaymentBehaviourService
    {
        return app(PaymentBehaviourService::class);
    }

    public function test_tenants_with_identical_arrears_but_different_histories_are_distinguished(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $unit = $this->unit($property, 10000);

        // Tenant A: owes 20,000, has paid nothing.
        $tenantA = $this->tenant($property, $unit, null, 'Tenant A');
        $a2 = now()->subMonths(2);
        $a1 = now()->subMonth();
        $this->rent($tenantA, $property, $unit, $a2->month, $a2->year, 10000, $a2->copy()->day(5)->toDateString());
        $this->rent($tenantA, $property, $unit, $a1->month, $a1->year, 10000, $a1->copy()->day(5)->toDateString());

        // Tenant B: owes 20,000 but has paid 10,000 in instalments.
        $tenantB = $this->tenant($property, $unit, null, 'Tenant B');
        $b2 = now()->subMonths(2);
        $b1 = now()->subMonth();
        $this->rent($tenantB, $property, $unit, $b2->month, $b2->year, 10000, $b2->copy()->day(5)->toDateString());
        $this->rent($tenantB, $property, $unit, $b1->month, $b1->year, 10000, $b1->copy()->day(5)->toDateString());
        $this->rent($tenantB, $property, $unit, now()->month, now()->year, 10000, now()->copy()->day(5)->toDateString());

        $this->allocation()->recordPayment($tenantB, 4000, ['payment_date' => now()->subDays(20)->toDateString()]);
        $this->allocation()->recordPayment($tenantB, 3000, ['payment_date' => now()->subDays(10)->toDateString()]);
        $this->allocation()->recordPayment($tenantB, 3000, ['payment_date' => now()->toDateString()]);

        $from = now()->subMonths(2)->startOfMonth();

        $a = $this->behaviour()->indicators($tenantA, $from);
        $b = $this->behaviour()->indicators($tenantB, $from);

        // Same outstanding balance...
        $this->assertSame(20000.0, $a['outstanding_balance']);
        $this->assertSame(20000.0, $b['outstanding_balance']);

        // ...but clearly different payment behaviour.
        $this->assertSame(0, $a['payments_count']);
        $this->assertNull($a['last_payment_date']);
        $this->assertSame(3, $b['payments_count']);
        $this->assertNotNull($b['last_payment_date']);
        $this->assertSame(10000.0, $b['paid_in_period']);
    }

    public function test_trend_decreasing_when_balance_reduced(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $unit = $this->unit($property, 10000);
        $tenant = $this->tenant($property, $unit);

        $old = now()->subMonths(3);
        $this->rent($tenant, $property, $unit, $old->month, $old->year, 10000, $old->copy()->day(5)->toDateString());

        $this->allocation()->recordPayment($tenant, 4000, ['payment_date' => now()->toDateString()]);

        $indicators = $this->behaviour()->indicators($tenant, now()->subMonths(2)->startOfMonth());

        $this->assertSame('decreasing', $indicators['trend']);
    }

    public function test_trend_increasing_when_new_charges_added(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $unit = $this->unit($property, 10000);
        $tenant = $this->tenant($property, $unit);

        $recent = now()->subMonth();
        $this->rent($tenant, $property, $unit, $recent->month, $recent->year, 10000, $recent->copy()->day(5)->toDateString());

        $indicators = $this->behaviour()->indicators($tenant, now()->subMonths(3)->startOfMonth());

        $this->assertSame('increasing', $indicators['trend']);
    }

    public function test_trend_unchanged_without_activity(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $unit = $this->unit($property, 10000);
        $tenant = $this->tenant($property, $unit);

        $old = now()->subMonths(3);
        $this->rent($tenant, $property, $unit, $old->month, $old->year, 10000, $old->copy()->day(5)->toDateString());

        $indicators = $this->behaviour()->indicators($tenant, now()->subMonths(2)->startOfMonth());

        $this->assertSame('unchanged', $indicators['trend']);
    }
}
