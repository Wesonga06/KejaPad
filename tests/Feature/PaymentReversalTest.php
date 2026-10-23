<?php

namespace Tests\Feature;

use App\Models\PaymentReversal;
use App\Models\User;
use App\Policies\PaymentReversalPolicy;
use App\Services\PaymentReversalService;
use App\Services\RentAllocationService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Concerns\CreatesRentScenario;
use Tests\TestCase;

class PaymentReversalTest extends TestCase
{
    use CreatesRentScenario;
    use RefreshDatabase;

    public function test_approved_reversal_updates_credit_and_rent_without_deleting_original_allocations(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $unit = $this->unit($property);
        $tenant = $this->tenant($property, $unit);
        $rent = $this->rent($tenant, $property, $unit, now()->month, now()->year);
        $payment = app(RentAllocationService::class)->recordPayment($tenant, 12000);
        $sourceAllocation = $payment->allocations->first();
        $initiator = $landlord;
        $approver = User::factory()->create(['role' => 'super_admin']);
        $service = app(PaymentReversalService::class);

        $reversal = $service->initiate($payment, $initiator, 3000, 2000, 'Duplicate amount returned', [$sourceAllocation->id => 1000]);
        $this->assertSame('pending', $reversal->status);
        $this->assertSame(0.0, $tenant->fresh()->creditBalance());

        $service->decide($reversal, $approver, true);

        try {
            $service->decide($reversal->fresh(), $approver, true);
            $this->fail('A decided reversal must not be approved a second time.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        $this->assertSame('approved', $reversal->fresh()->status);
        $this->assertSame('0.00', (string) $payment->fresh()->unallocated_amount);
        $this->assertSame('9000.00', (string) $rent->fresh()->amount_paid);
        $this->assertSame(10000.0, (float) $payment->fresh()->allocations()->sum('amount'));
        $this->assertSame(9000.0, $payment->fresh()->allocatedAmount());
        $this->assertDatabaseHas('rent_audits', ['auditable_type' => PaymentReversal::class, 'auditable_id' => $reversal->id, 'action' => 'payment_reversal_approved']);

        $payment->update(['method' => 'mpesa']);
        $service->recordExternalRefund($reversal->fresh(), $approver, 'confirmed', 'PROVIDER-REF-123');
        $this->assertDatabaseHas('payment_reversals', ['id' => $reversal->id, 'external_refund_status' => 'confirmed', 'external_refund_reference' => 'PROVIDER-REF-123']);
        $this->assertDatabaseHas('rent_audits', ['auditable_type' => PaymentReversal::class, 'auditable_id' => $reversal->id, 'action' => 'external_refund_updated']);
    }

    public function test_pending_reversal_reserves_credit_and_rejecting_releases_reservation(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $unit = $this->unit($property);
        $tenant = $this->tenant($property, $unit);
        $payment = app(RentAllocationService::class)->recordPayment($tenant, 1000);
        $rent = $this->rent($tenant, $property, $unit, now()->month, now()->year);
        $service = app(PaymentReversalService::class);
        $reversal = $service->initiate($payment, $landlord, 600, 600, 'Return excess credit', []);

        $this->assertSame(400.0, $tenant->fresh()->creditBalance());
        $this->assertSame(400.0, app(RentAllocationService::class)->applyTenantCredit($tenant));
        $this->assertSame('600.00', (string) $payment->fresh()->unallocated_amount);
        $this->assertSame('400.00', (string) $rent->fresh()->amount_paid);
        try {
            $service->initiate($payment, $landlord, 500, 500, 'Duplicate request', []);
            $this->fail('Pending credit reservation should reject excess requests.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
        $service->decide($reversal, User::factory()->create(['role' => 'super_admin']), false, 'Not approved');
        $this->assertSame(600.0, $tenant->fresh()->creditBalance());
        $this->assertSame(600.0, app(RentAllocationService::class)->applyTenantCredit($tenant));
        $this->assertSame('1000.00', (string) $rent->fresh()->amount_paid);
    }

    public function test_initiator_cannot_approve_and_pending_split_must_match_requested_amount(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $unit = $this->unit($property);
        $tenant = $this->tenant($property, $unit);
        $payment = app(RentAllocationService::class)->recordPayment($tenant, 1000);
        $service = app(PaymentReversalService::class);
        $this->expectException(ValidationException::class);
        $service->initiate($payment, $landlord, 500, 400, 'Mismatch', []);
    }

    public function test_initiator_cannot_approve_own_pending_reversal(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $unit = $this->unit($property);
        $tenant = $this->tenant($property, $unit);
        $payment = app(RentAllocationService::class)->recordPayment($tenant, 1000);
        $service = app(PaymentReversalService::class);
        $reversal = $service->initiate($payment, $landlord, 500, 500, 'Duplicate credit', []);
        $this->expectException(ValidationException::class);
        $service->decide($reversal, $landlord, true);
    }

    public function test_policy_limits_reversal_initiation_to_the_payment_owner_and_decisions_to_super_admins(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $unit = $this->unit($property);
        $tenant = $this->tenant($property, $unit);
        $payment = app(RentAllocationService::class)->recordPayment($tenant, 1000);
        $otherLandlord = $this->landlord();
        $policy = app(PaymentReversalPolicy::class);

        $this->assertFalse($policy->create($otherLandlord, $payment));
        $this->assertFalse($policy->approve($landlord, new PaymentReversal));
        $this->assertTrue($policy->approve(User::factory()->create(['role' => 'super_admin']), new PaymentReversal));
    }

    public function test_payment_with_reversal_history_cannot_be_deleted(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $unit = $this->unit($property);
        $tenant = $this->tenant($property, $unit);
        $payment = app(RentAllocationService::class)->recordPayment($tenant, 1000);
        app(PaymentReversalService::class)->initiate($payment, $landlord, 250, 250, 'Refund request', []);

        $this->expectException(QueryException::class);
        $payment->delete();
    }
}
