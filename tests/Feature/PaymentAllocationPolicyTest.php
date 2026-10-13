<?php

namespace Tests\Feature;

use App\Services\RentAllocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesRentScenario;
use Tests\TestCase;

class PaymentAllocationPolicyTest extends TestCase
{
    use CreatesRentScenario;
    use RefreshDatabase;

    private function allocation(): RentAllocationService
    {
        return app(RentAllocationService::class);
    }

    public function test_oldest_first_policy_pays_oldest_arrears_first(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $unit = $this->unit($property, 10000);
        $tenant = $this->tenant($property, $unit);

        $older = now()->subMonths(2);
        $newer = now()->subMonth();

        $old = $this->rent($tenant, $property, $unit, $older->month, $older->year, 10000, $older->copy()->day(5)->toDateString());
        $new = $this->rent($tenant, $property, $unit, $newer->month, $newer->year, 10000, $newer->copy()->day(5)->toDateString());

        $this->allocation()->recordPayment($tenant, 4000, [
            'payment_date' => now()->toDateString(),
            'policy' => RentAllocationService::POLICY_OLDEST,
        ]);

        $this->assertSame('4000.00', (string) $old->fresh()->amount_paid);
        $this->assertSame('0.00', (string) $new->fresh()->amount_paid);
    }

    public function test_current_first_policy_pays_most_recent_arrears_first(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $unit = $this->unit($property, 10000);
        $tenant = $this->tenant($property, $unit);

        $older = now()->subMonths(2);
        $newer = now()->subMonth();

        $old = $this->rent($tenant, $property, $unit, $older->month, $older->year, 10000, $older->copy()->day(5)->toDateString());
        $new = $this->rent($tenant, $property, $unit, $newer->month, $newer->year, 10000, $newer->copy()->day(5)->toDateString());

        $this->allocation()->recordPayment($tenant, 4000, [
            'payment_date' => now()->toDateString(),
            'policy' => RentAllocationService::POLICY_CURRENT,
        ]);

        $this->assertSame('0.00', (string) $old->fresh()->amount_paid);
        $this->assertSame('4000.00', (string) $new->fresh()->amount_paid);
    }

    public function test_manual_policy_targets_a_selected_period(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $unit = $this->unit($property, 10000);
        $tenant = $this->tenant($property, $unit);

        $older = now()->subMonths(2);
        $newer = now()->subMonth();

        $old = $this->rent($tenant, $property, $unit, $older->month, $older->year, 10000, $older->copy()->day(5)->toDateString());
        $new = $this->rent($tenant, $property, $unit, $newer->month, $newer->year, 10000, $newer->copy()->day(5)->toDateString());

        $this->allocation()->recordPayment($tenant, 4000, [
            'payment_date' => now()->toDateString(),
            'policy' => RentAllocationService::POLICY_MANUAL,
            'rent_record_id' => $new->id,
        ]);

        $this->assertSame('0.00', (string) $old->fresh()->amount_paid);
        $this->assertSame('4000.00', (string) $new->fresh()->amount_paid);
    }

    public function test_a_payment_that_exceeds_one_period_spills_into_the_next(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $unit = $this->unit($property, 10000);
        $tenant = $this->tenant($property, $unit);

        $older = now()->subMonths(2);
        $newer = now()->subMonth();

        $old = $this->rent($tenant, $property, $unit, $older->month, $older->year, 10000, $older->copy()->day(5)->toDateString());
        $new = $this->rent($tenant, $property, $unit, $newer->month, $newer->year, 10000, $newer->copy()->day(5)->toDateString());

        $payment = $this->allocation()->recordPayment($tenant, 15000, [
            'payment_date' => now()->toDateString(),
            'policy' => RentAllocationService::POLICY_OLDEST,
        ]);

        $this->assertSame('10000.00', (string) $old->fresh()->amount_paid);
        $this->assertSame('5000.00', (string) $new->fresh()->amount_paid);
        $this->assertSame('0.00', (string) $payment->fresh()->unallocated_amount);
        $this->assertCount(2, $payment->fresh()->allocations);
    }
}
