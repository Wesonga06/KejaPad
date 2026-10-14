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
}
