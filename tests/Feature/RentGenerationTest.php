<?php

namespace Tests\Feature;

use App\Models\RentRecord;
use App\Services\RentAllocationService;
use App\Services\RentGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesRentScenario;
use Tests\TestCase;

class RentGenerationTest extends TestCase
{
    use CreatesRentScenario;
    use RefreshDatabase;

    private function generation(): RentGenerationService
    {
        return app(RentGenerationService::class);
    }

    public function test_it_generates_a_record_due_on_the_configured_day(): void
    {
        config()->set('rent.due_day', 3);

        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $unit = $this->unit($property, 12000);
        $tenant = $this->tenant($property, $unit);

        $result = $this->generation()->generate(now()->month, now()->year);

        $this->assertSame(1, $result['created']);

        $rent = RentRecord::where('tenant_id', $tenant->id)->firstOrFail();
        $this->assertSame('12000.00', (string) $rent->amount_due);
        $this->assertSame(
            sprintf('%04d-%02d-03', now()->year, now()->month),
            $rent->due_date->toDateString()
        );
        $this->assertSame('pending', $rent->status);
    }

    public function test_it_is_idempotent_and_does_not_create_duplicates(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $unit = $this->unit($property, 10000);
        $this->tenant($property, $unit);

        $first = $this->generation()->generate(now()->month, now()->year);
        $second = $this->generation()->generate(now()->month, now()->year);

        $this->assertSame(1, $first['created']);
        $this->assertSame(0, $second['created']);
        $this->assertSame(1, $second['skipped']);
        $this->assertDatabaseCount('rent_records', 1);
    }

    public function test_it_skips_inactive_tenants_and_tenants_without_a_unit(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $unit = $this->unit($property, 10000);

        $active = $this->tenant($property, $unit, null, 'Active');
        $inactive = $this->tenant($property, $unit, null, 'Inactive');
        $inactive->update(['is_active' => false]);

        $result = $this->generation()->generate(now()->month, now()->year);

        $this->assertSame(1, $result['created']);
        $this->assertDatabaseHas('rent_records', ['tenant_id' => $active->id]);
        $this->assertDatabaseMissing('rent_records', ['tenant_id' => $inactive->id]);
    }

    public function test_it_only_generates_for_the_selected_properties(): void
    {
        $landlord = $this->landlord();
        $propertyA = $this->property($landlord);
        $propertyB = $this->property($landlord);
        $tenantA = $this->tenant($propertyA, $this->unit($propertyA));
        $tenantB = $this->tenant($propertyB, $this->unit($propertyB));

        $result = $this->generation()->generate(now()->month, now()->year, [$propertyA->id]);

        $this->assertSame(1, $result['created']);
        $this->assertDatabaseHas('rent_records', ['tenant_id' => $tenantA->id]);
        $this->assertDatabaseMissing('rent_records', ['tenant_id' => $tenantB->id]);
    }

    public function test_the_command_generates_rent_for_the_current_month(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $unit = $this->unit($property, 10000);
        $tenant = $this->tenant($property, $unit);

        $this->artisan('rent:generate')->assertSuccessful();

        $this->assertDatabaseHas('rent_records', [
            'tenant_id' => $tenant->id,
            'month' => now()->month,
            'year' => now()->year,
        ]);
    }

    public function test_the_command_respects_the_auto_generate_switch(): void
    {
        config()->set('rent.auto_generate', false);

        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $unit = $this->unit($property, 10000);
        $this->tenant($property, $unit);

        $this->artisan('rent:generate')->assertSuccessful();
        $this->assertDatabaseCount('rent_records', 0);

        $this->artisan('rent:generate', ['--force' => true])->assertSuccessful();
        $this->assertDatabaseCount('rent_records', 1);
    }

    public function test_held_credit_is_automatically_deducted_from_new_rent(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $unit = $this->unit($property, 10000);
        $tenant = $this->tenant($property, $unit);

        // Tenant pays in full for a period that has no record yet -> held as credit.
        $payment = app(RentAllocationService::class)->recordPayment($tenant, 10000, [
            'payment_date' => now()->subDays(2)->toDateString(),
            'reference' => 'CREDIT-1',
        ]);
        $this->assertSame('10000.00', (string) $payment->fresh()->unallocated_amount);

        // A new month starts; the record is generated and the credit applied automatically.
        $next = now()->addMonthNoOverflow();
        $result = $this->generation()->generate($next->month, $next->year);

        $this->assertSame(10000.0, $result['credit_applied']);

        $rent = RentRecord::where('tenant_id', $tenant->id)->firstOrFail();
        $this->assertSame('10000.00', (string) $rent->amount_paid);
        $this->assertSame('paid', $rent->status);
        $this->assertSame('0.00', (string) $payment->fresh()->unallocated_amount);
    }

    public function test_partial_credit_leaves_the_remainder_due(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $unit = $this->unit($property, 10000);
        $tenant = $this->tenant($property, $unit);

        app(RentAllocationService::class)->recordPayment($tenant, 4000, [
            'payment_date' => now()->subDays(2)->toDateString(),
        ]);

        $next = now()->addMonthNoOverflow();
        $this->generation()->generate($next->month, $next->year);

        $rent = RentRecord::where('tenant_id', $tenant->id)->firstOrFail();
        $this->assertSame('4000.00', (string) $rent->amount_paid);
        $this->assertSame('6000.00', number_format($rent->balance(), 2, '.', ''));
        $this->assertSame('partial', $rent->status);
    }

    public function test_credit_is_applied_oldest_first_to_existing_arrears(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $unit = $this->unit($property, 10000);
        $tenant = $this->tenant($property, $unit);

        // Two unpaid months, then an overpayment allocated to the newest period only.
        $older = now()->subMonths(2);
        $newer = now()->subMonth();
        $old = $this->rent($tenant, $property, $unit, $older->month, $older->year, 10000, $older->copy()->day(3)->toDateString());
        $new = $this->rent($tenant, $property, $unit, $newer->month, $newer->year, 10000, $newer->copy()->day(3)->toDateString());

        $payment = app(RentAllocationService::class)->recordPayment($tenant, 20000, [
            'payment_date' => now()->subDays(2)->toDateString(),
            'policy' => RentAllocationService::POLICY_MANUAL,
            'rent_record_id' => $new->id,
        ]);

        // 10,000 covers the selected period; the remaining 10,000 is held as credit.
        $this->assertSame('10000.00', (string) $payment->fresh()->unallocated_amount);
        $this->assertSame('0.00', (string) $old->fresh()->amount_paid);

        app(RentAllocationService::class)->applyTenantCredit($tenant);

        $this->assertSame('10000.00', (string) $old->fresh()->amount_paid);
        $this->assertSame('paid', $old->fresh()->status);
        $this->assertSame('0.00', (string) $payment->fresh()->unallocated_amount);
    }

    public function test_held_credit_and_a_later_payment_cannot_allocate_more_than_the_rent_due(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $unit = $this->unit($property, 10000);
        $tenant = $this->tenant($property, $unit);

        $held = app(RentAllocationService::class)->recordPayment($tenant, 7000, [
            'payment_date' => now()->toDateString(),
        ]);

        $this->generation()->generate(now()->month, now()->year);
        $later = app(RentAllocationService::class)->recordPayment($tenant, 7000, [
            'payment_date' => now()->toDateString(),
        ]);

        $rent = RentRecord::where('tenant_id', $tenant->id)->firstOrFail();

        $this->assertSame('10000.00', (string) $rent->fresh()->amount_paid);
        $this->assertSame('0.00', (string) $held->fresh()->unallocated_amount);
        $this->assertSame('4000.00', (string) $later->fresh()->unallocated_amount);
        $this->assertSame(10000.0, (float) $rent->allocations()->sum('amount'));
    }
}
