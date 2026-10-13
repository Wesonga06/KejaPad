<?php

namespace Tests\Feature;

use App\Services\RentAllocationService;
use App\Services\RentStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Concerns\CreatesRentScenario;
use Tests\TestCase;

class PartialPaymentTest extends TestCase
{
    use CreatesRentScenario;
    use RefreshDatabase;

    private function allocation(): RentAllocationService
    {
        return app(RentAllocationService::class);
    }

    public function test_partial_payment_reduces_balance_and_records_transaction(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $unit = $this->unit($property, 12000);
        $tenant = $this->tenant($property, $unit);
        $rent = $this->rent($tenant, $property, $unit, now()->month, now()->year, 12000, now()->addDays(10)->toDateString());

        $payment = $this->allocation()->recordPayment($tenant, 5000, [
            'payment_date' => now()->toDateString(),
            'reference' => 'REF-001',
            'method' => 'cash',
            'source' => 'landlord',
        ]);

        $rent->refresh();

        $this->assertSame('5000.00', (string) $rent->amount_paid);
        $this->assertSame('7000.00', number_format($rent->balance(), 2, '.', ''));
        $this->assertSame('partial', $rent->status);

        // Individual transaction preserved with date, amount, reference and period.
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'amount' => 5000,
            'reference' => 'REF-001',
        ]);
        $this->assertDatabaseHas('payment_allocations', [
            'payment_id' => $payment->id,
            'rent_record_id' => $rent->id,
            'amount' => 5000,
        ]);
        $this->assertSame('0.00', (string) $payment->fresh()->unallocated_amount);
    }

    public function test_unpaid_rent_carries_forward_into_next_month(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $unit = $this->unit($property, 12000);
        $tenant = $this->tenant($property, $unit);

        $lastMonth = now()->subMonthNoOverflow();
        $this->rent($tenant, $property, $unit, $lastMonth->month, $lastMonth->year, 12000, $lastMonth->copy()->day(5)->toDateString());
        $this->rent($tenant, $property, $unit, now()->month, now()->year, 12000, now()->copy()->day(5)->toDateString());

        // Pay 5,000 towards arrears.
        $this->allocation()->recordPayment($tenant, 5000, ['payment_date' => now()->toDateString()]);

        $summary = app(RentStatusService::class)->summary($tenant);

        // 12,000 (last month, now 7,000 outstanding after 5,000) + 12,000 (this month) = 19,000.
        $this->assertSame(19000.0, $summary['outstanding']);

        $breakdown = app(RentStatusService::class)->breakdown($tenant);
        $this->assertCount(2, $breakdown);

        $oldest = $breakdown->first();
        $this->assertSame('7000.00', number_format($oldest->balance, 2, '.', ''));
    }

    public function test_overpayment_is_held_as_unallocated_credit(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $unit = $this->unit($property, 12000);
        $tenant = $this->tenant($property, $unit);
        $rent = $this->rent($tenant, $property, $unit, now()->month, now()->year, 12000, now()->addDays(5)->toDateString());

        $payment = $this->allocation()->recordPayment($tenant, 15000, ['payment_date' => now()->toDateString()]);

        $rent->refresh();

        $this->assertSame('12000.00', (string) $rent->amount_paid);
        $this->assertSame('paid', $rent->status);
        $this->assertSame('3000.00', (string) $payment->fresh()->unallocated_amount);
    }

    public function test_duplicate_reference_is_rejected(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $unit = $this->unit($property, 12000);
        $tenant = $this->tenant($property, $unit);
        $this->rent($tenant, $property, $unit, now()->month, now()->year, 12000, now()->addDays(5)->toDateString());

        $this->allocation()->recordPayment($tenant, 1000, ['payment_date' => now()->toDateString(), 'reference' => 'DUP-1']);

        $this->expectException(ValidationException::class);

        $this->allocation()->recordPayment($tenant, 1000, ['payment_date' => now()->toDateString(), 'reference' => 'DUP-1']);
    }

    public function test_partial_payment_does_not_clear_overdue_account(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $unit = $this->unit($property, 12000);
        $tenant = $this->tenant($property, $unit);
        $rent = $this->rent($tenant, $property, $unit, now()->month, now()->year, 12000, now()->subDays(10)->toDateString());

        $this->allocation()->recordPayment($tenant, 3000, ['payment_date' => now()->toDateString()]);

        $rent->refresh();
        $summary = app(RentStatusService::class)->summary($tenant);

        $this->assertSame('overdue', $rent->status);
        $this->assertSame('9000.00', number_format($rent->balance(), 2, '.', ''));
        $this->assertNotSame('green', $summary['status_key']);
        $this->assertSame(9000.0, $summary['arrears']);
    }

    public function test_full_payment_settles_arrears_and_clears_status(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $unit = $this->unit($property, 12000);
        $tenant = $this->tenant($property, $unit);
        $rent = $this->rent($tenant, $property, $unit, now()->month, now()->year, 12000, now()->subDays(30)->toDateString());

        $this->allocation()->recordPayment($tenant, 12000, ['payment_date' => now()->toDateString()]);

        $rent->refresh();
        $summary = app(RentStatusService::class)->summary($tenant);

        $this->assertSame('paid', $rent->status);
        $this->assertSame(0.0, $summary['arrears']);
        $this->assertSame('green', $summary['status_key']);
    }
}
