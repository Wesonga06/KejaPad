<?php

namespace Tests\Feature;

use App\Models\DepositTransaction;
use App\Models\SecurityDeposit;
use App\Models\Tenant;
use App\Services\DepositService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesMoveOutScenario;
use Tests\TestCase;

class DepositTest extends TestCase
{
    use CreatesMoveOutScenario, RefreshDatabase;

    private array $ctx;

    protected function setUp(): void
    {
        parent::setUp();
        $owner = $this->makeLandlord();
        $property = $this->makeProperty($owner);
        $unit = $this->makeUnit($property, 10000, 'reserved');
        $tenant = $this->makeTenant($property, $unit);
        $deposit = $this->makeDeposit($tenant, $property, $unit, 10000);

        $this->ctx = compact('owner', 'property', 'unit', 'tenant', 'deposit');
    }

    public function test_deposit_obligation_is_created_when_tenant_is_added_and_unit_reserved(): void
    {
        $owner = $this->makeLandlord();
        $property = $this->makeProperty($owner);
        $unit = $this->makeUnit($property, 10000, 'vacant');

        $this->actingAs($owner)
            ->post(route('landlord.tenants.store'), [
                'full_name' => 'New Tenant',
                'phone' => '254700000000',
                'property_id' => $property->id,
                'unit_id' => $unit->id,
                'move_in_date' => now()->toDateString(),
            ])
            ->assertRedirect(route('landlord.tenants.index'));

        $tenant = Tenant::where('full_name', 'New Tenant')->firstOrFail();
        $deposit = $tenant->securityDeposit;

        $this->assertNotNull($deposit);
        $this->assertEquals(10000, (float) $deposit->required_amount);
        $this->assertSame('reserved', $unit->fresh()->status);
    }

    public function test_tenant_cannot_be_assigned_to_a_unit_from_another_property(): void
    {
        $owner = $this->makeLandlord();
        $property = $this->makeProperty($owner);
        $otherProperty = $this->makeProperty($owner);
        $unit = $this->makeUnit($otherProperty, 10000, 'vacant');

        $this->actingAs($owner)
            ->post(route('landlord.tenants.store'), [
                'full_name' => 'Wrong Property Tenant',
                'phone' => '254700000001',
                'property_id' => $property->id,
                'unit_id' => $unit->id,
                'move_in_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('unit_id');

        $this->assertDatabaseMissing('tenants', ['full_name' => 'Wrong Property Tenant']);
        $this->assertDatabaseMissing('security_deposits', ['unit_id' => $unit->id]);
        $this->assertSame('vacant', $unit->fresh()->status);
    }

    public function test_tenant_cannot_be_assigned_when_an_active_tenancy_exists_even_if_unit_is_marked_vacant(): void
    {
        $owner = $this->makeLandlord();
        $property = $this->makeProperty($owner);
        $unit = $this->makeUnit($property, 10000, 'vacant');
        $existingTenant = $this->makeTenant($property, $unit, name: 'Existing Tenant');

        $this->actingAs($owner)
            ->post(route('landlord.tenants.store'), [
                'full_name' => 'Duplicate Unit Tenant',
                'phone' => '254700000002',
                'property_id' => $property->id,
                'unit_id' => $unit->id,
                'move_in_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('unit_id');

        $this->assertDatabaseMissing('tenants', ['full_name' => 'Duplicate Unit Tenant']);
        $this->assertSame($existingTenant->id, Tenant::where('unit_id', $unit->id)->first()->id);
        $this->assertDatabaseCount('security_deposits', 1);
        $this->assertSame('vacant', $unit->fresh()->status);
    }

    public function test_full_receipt_and_verification_mark_unit_occupied(): void
    {
        $deposit = $this->ctx['deposit'];
        $unit = $this->ctx['unit'];
        $owner = $this->ctx['owner'];

        $this->actingAs($owner)->post(route('landlord.deposits.receipts.store', $deposit), [
            'amount' => 10000,
            'method' => 'cash',
            'verify' => 1,
        ])->assertRedirect();

        $this->actingAs($owner)
            ->post(route('landlord.deposits.verify', $deposit))
            ->assertRedirect();

        $this->assertTrue($deposit->fresh()->isVerified());
        $this->assertTrue(app(DepositService::class)->isMoveInEligible($deposit->fresh()));
        $this->assertSame('occupied', $unit->fresh()->status);
    }

    public function test_partial_receipt_is_not_move_in_eligible(): void
    {
        $deposit = $this->ctx['deposit'];
        $owner = $this->ctx['owner'];

        $this->actingAs($owner)->post(route('landlord.deposits.receipts.store', $deposit), [
            'amount' => 4000,
            'method' => 'cash',
            'verify' => 1,
        ])->assertRedirect();

        $deposit->refresh();
        $this->assertSame(4000.0, (float) $deposit->received_amount);
        $this->assertFalse(app(DepositService::class)->isMoveInEligible($deposit));
    }

    public function test_proposed_deduction_only_counts_once_approved(): void
    {
        $deposit = $this->ctx['deposit'];
        $owner = $this->ctx['owner'];
        $service = app(DepositService::class);

        $service->recordReceipt($deposit, ['amount' => 10000, 'verify' => true], $owner);
        $deduction = $service->proposeDeduction($deposit->fresh(), ['amount' => 2500, 'reason' => 'Broken door'], $owner);

        $this->assertSame(10000.0, $deposit->fresh()->netRefundable());

        $service->approveDeduction($deduction, $owner);

        $this->assertSame(7500.0, $deposit->fresh()->netRefundable());
        $this->assertSame(DepositTransaction::STATUS_APPROVED, $deduction->fresh()->status);
    }

    public function test_standalone_deduction_approval_cannot_exceed_deposit_received(): void
    {
        $deposit = $this->ctx['deposit'];
        $owner = $this->ctx['owner'];
        $service = app(DepositService::class);
        $service->recordReceipt($deposit, ['amount' => 10000, 'verify' => true], $owner);

        $first = $service->proposeDeduction($deposit->fresh(), [
            'amount' => 7000,
            'reason' => 'Repair one',
        ], $owner);
        $second = $service->proposeDeduction($deposit->fresh(), [
            'amount' => 4000,
            'reason' => 'Repair two',
        ], $owner);

        $this->actingAs($owner)
            ->post(route('landlord.deposits.deductions.approve', [$deposit, $first]))
            ->assertRedirect();

        $this->actingAs($owner)
            ->post(route('landlord.deposits.deductions.approve', [$deposit, $second]))
            ->assertStatus(422);

        $this->assertSame(DepositTransaction::STATUS_APPROVED, $first->fresh()->status);
        $this->assertSame(DepositTransaction::STATUS_PROPOSED, $second->fresh()->status);
        $this->assertSame(7000.0, $deposit->fresh()->approvedDeductions());
    }

    public function test_deduction_approval_cannot_consume_a_reserved_refund(): void
    {
        $deposit = $this->ctx['deposit'];
        $owner = $this->ctx['owner'];
        $service = app(DepositService::class);
        $service->recordReceipt($deposit, ['amount' => 10000, 'verify' => true], $owner);
        $refund = $service->proposeRefund($deposit->fresh(), ['amount' => 7000], $owner);
        $deduction = $service->proposeDeduction($deposit->fresh(), [
            'amount' => 4000,
            'reason' => 'Repair',
        ], $owner);

        $this->actingAs($owner)
            ->post(route('landlord.deposits.deductions.approve', [$deposit, $deduction]))
            ->assertStatus(422);

        $this->assertSame(DepositTransaction::STATUS_PROPOSED, $deduction->fresh()->status);
        $this->assertSame(DepositTransaction::STATUS_PROPOSED, $refund->fresh()->status);
    }

    public function test_direct_refund_cannot_spend_an_already_reserved_refund(): void
    {
        $deposit = $this->ctx['deposit'];
        $owner = $this->ctx['owner'];
        $service = app(DepositService::class);
        $service->recordReceipt($deposit, ['amount' => 10000, 'verify' => true], $owner);
        $reservation = $service->proposeRefund($deposit->fresh(), ['amount' => 7000], $owner);

        $this->actingAs($owner)
            ->post(route('landlord.deposits.refunds.store', $deposit), [
                'amount' => 4000,
                'method' => 'mpesa',
                'reference' => 'REF-RESERVED',
            ])
            ->assertStatus(422);

        $this->assertSame(DepositTransaction::STATUS_PROPOSED, $reservation->fresh()->status);
        $this->assertSame(0, $deposit->fresh()->refunds()->where('status', DepositTransaction::STATUS_COMPLETED)->count());
    }

    public function test_duplicate_refund_reference_is_rejected_without_a_second_refund(): void
    {
        $deposit = $this->ctx['deposit'];
        $owner = $this->ctx['owner'];
        app(DepositService::class)->recordReceipt($deposit, ['amount' => 10000, 'verify' => true], $owner);

        $this->actingAs($owner)
            ->post(route('landlord.deposits.refunds.store', $deposit), [
                'amount' => 5000,
                'method' => 'mpesa',
                'reference' => 'REF-DUPLICATE',
            ])
            ->assertRedirect();

        $this->actingAs($owner)
            ->post(route('landlord.deposits.refunds.store', $deposit), [
                'amount' => 5000,
                'method' => 'mpesa',
                'reference' => 'REF-DUPLICATE',
            ])
            ->assertStatus(422);

        $this->assertSame(1, $deposit->fresh()->refunds()
            ->where('status', DepositTransaction::STATUS_COMPLETED)
            ->where('reference', 'REF-DUPLICATE')
            ->count());
    }

    public function test_deposit_details_show_the_balance_available_after_refund_reservations(): void
    {
        $deposit = $this->ctx['deposit'];
        $owner = $this->ctx['owner'];
        $service = app(DepositService::class);
        $service->recordReceipt($deposit, ['amount' => 10000, 'verify' => true], $owner);
        $service->proposeRefund($deposit->fresh(), ['amount' => 7000], $owner);

        $this->actingAs($owner)
            ->get(route('landlord.deposits.show', $deposit))
            ->assertOk()
            ->assertSee('Available to Refund')
            ->assertSee('3,000.00')
            ->assertSee('pending refunds');
    }

    public function test_another_landlord_cannot_approve_a_deposit_deduction(): void
    {
        $deposit = $this->ctx['deposit'];
        $service = app(DepositService::class);
        $service->recordReceipt($deposit, ['amount' => 10000], $this->ctx['owner']);
        $deduction = $service->proposeDeduction($deposit->fresh(), [
            'amount' => 1000,
            'reason' => 'Repair',
        ], $this->ctx['owner']);
        $otherLandlord = $this->makeLandlord();

        $this->actingAs($otherLandlord)
            ->post(route('landlord.deposits.deductions.approve', [$deposit, $deduction]))
            ->assertForbidden();

        $this->assertSame(DepositTransaction::STATUS_PROPOSED, $deduction->fresh()->status);
    }

    public function test_refund_cannot_exceed_net_refundable(): void
    {
        $deposit = $this->ctx['deposit'];
        $owner = $this->ctx['owner'];
        $service = app(DepositService::class);

        $service->recordReceipt($deposit, ['amount' => 1000, 'verify' => true], $owner);

        $this->actingAs($owner)->post(route('landlord.deposits.refunds.store', $deposit), [
            'amount' => 5000,
            'method' => 'cash',
        ])->assertStatus(422);
    }

    public function test_full_refund_marks_deposit_refunded(): void
    {
        $deposit = $this->ctx['deposit'];
        $owner = $this->ctx['owner'];
        $service = app(DepositService::class);

        $service->recordReceipt($deposit, ['amount' => 10000, 'verify' => true], $owner);

        $this->actingAs($owner)->post(route('landlord.deposits.refunds.store', $deposit), [
            'amount' => 10000,
            'method' => 'mpesa',
            'reference' => 'REF123',
        ])->assertRedirect();

        $deposit->refresh();
        $this->assertSame(0.0, $deposit->netRefundable());
        $this->assertSame(SecurityDeposit::STATUS_REFUNDED, $deposit->derivedStatus());
    }

    public function test_landlord_can_generate_missing_deposit_records_for_existing_tenants(): void
    {
        $owner = $this->makeLandlord();
        $property = $this->makeProperty($owner);
        $unit = $this->makeUnit($property, 8000);
        $tenant = $this->makeTenant($property, $unit, null, 'Legacy Tenant');

        $this->assertNull($tenant->securityDeposit);

        $this->actingAs($owner)
            ->post(route('landlord.deposits.sync'))
            ->assertRedirect();

        $this->assertNotNull($tenant->fresh()->securityDeposit);
        $this->assertEquals(8000, (float) $tenant->fresh()->securityDeposit->required_amount);
    }

    public function test_authorized_exception_permits_move_in_without_full_deposit(): void
    {
        $deposit = $this->ctx['deposit'];
        $unit = $this->ctx['unit'];
        $owner = $this->ctx['owner'];

        $this->assertFalse(app(DepositService::class)->isMoveInEligible($deposit));

        $this->actingAs($owner)
            ->post(route('landlord.deposits.exception', $deposit), [
                'exception_reason' => 'Deposit waived by written landlord agreement.',
            ])
            ->assertRedirect();

        $deposit->refresh();

        $this->assertTrue($deposit->hasException());
        $this->assertSame($owner->id, $deposit->exception_approved_by);
        $this->assertTrue(app(DepositService::class)->isMoveInEligible($deposit));
        $this->assertSame('occupied', $unit->fresh()->status);
    }

    public function test_exception_requires_a_reason(): void
    {
        $deposit = $this->ctx['deposit'];
        $owner = $this->ctx['owner'];

        $this->actingAs($owner)
            ->post(route('landlord.deposits.exception', $deposit), [
                'exception_reason' => '',
            ])
            ->assertSessionHasErrors('exception_reason');

        $this->assertFalse($deposit->fresh()->hasException());
    }
}
