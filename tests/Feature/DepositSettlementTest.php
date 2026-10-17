<?php

namespace Tests\Feature;

use App\Models\DepositSettlement;
use App\Models\DepositSettlementEvent;
use App\Models\DepositTransaction;
use App\Models\MoveOutRequest;
use App\Models\SecurityDeposit;
use App\Models\UnitInspection;
use App\Services\DepositService;
use App\Services\DepositSettlementService;
use App\Services\MoveOutResolutionService;
use App\Services\MoveOutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesMoveOutScenario;
use Tests\TestCase;

class DepositSettlementTest extends TestCase
{
    use CreatesMoveOutScenario, RefreshDatabase;

    private function scenario(float $received = 10000): array
    {
        $owner = $this->makeLandlord();
        $user = $this->makeTenantUser();
        $property = $this->makeProperty($owner);
        $unit = $this->makeUnit($property);
        $tenant = $this->makeTenant($property, $unit, $user);
        $deposit = $this->makeDeposit($tenant, $property, $unit, 10000, $received);

        $service = app(MoveOutService::class);
        $moveOut = $service->createRequest($tenant, [
            'proposed_move_out_date' => now()->addMonthsNoOverflow(1)->toDateString(),
        ], $user);
        $service->transition($moveOut, MoveOutRequest::STATUS_APPROVED, $owner);

        return compact('owner', 'user', 'property', 'unit', 'tenant', 'deposit', 'moveOut', 'service');
    }

    private function inspectedScenario(): array
    {
        $ctx = $this->scenario();
        $caretaker = $this->makeCaretaker();
        $ctx['property']->caretakers()->attach($caretaker->id);

        $inspection = $ctx['service']->assignInspection($ctx['moveOut'], $caretaker, null, $ctx['owner']);
        $first = $inspection->items()->first();

        $ctx['service']->submitInspection($inspection, [
            'recommendation' => 'further_action',
            'items' => [
                $first->id => ['condition' => 'damaged', 'nature' => 'new_damage', 'comment' => 'Hole in wall'],
            ],
        ], $caretaker);

        $ctx['caretaker'] = $caretaker;
        $ctx['inspection'] = $inspection->fresh(['items']);
        $ctx['firstItem'] = $first;

        return $ctx;
    }

    public function test_settlement_held_amount_uses_received_not_required(): void
    {
        $ctx = $this->scenario(6000);

        $settlement = app(DepositSettlementService::class)->ensure($ctx['moveOut'], $ctx['owner']);

        // Only KES 6,000 was actually received of the KES 10,000 required.
        $this->assertSame(6000.0, (float) $settlement->held_amount);
        $this->assertSame(6000.0, (float) $settlement->proposed_refund);
        $this->assertSame(0.0, (float) $settlement->proposed_deductions);
    }

    public function test_starting_a_settlement_records_creation_event(): void
    {
        $ctx = $this->scenario();

        $settlement = app(DepositSettlementService::class)->ensure($ctx['moveOut'], $ctx['owner']);

        $this->assertSame(DepositSettlement::STATUS_DRAFT, $settlement->status);
        $this->assertDatabaseHas('deposit_settlements', ['move_out_request_id' => $ctx['moveOut']->id]);
        $this->assertTrue(
            $settlement->events()->where('event', DepositSettlementEvent::EVENT_CREATED)->exists()
        );
    }

    public function test_a_proposed_deduction_is_not_applied_until_approved(): void
    {
        $ctx = $this->scenario();
        $ctx['deposits'] = app(DepositService::class);

        $ctx['deposits']->recordReceipt($ctx['deposit'], ['amount' => 10000, 'verify' => true], $ctx['owner']);
        $deduction = $ctx['deposits']->proposeDeduction($ctx['deposit']->fresh(), [
            'amount' => 2000,
            'reason' => 'Broken door',
        ], $ctx['owner']);

        $settlement = app(DepositSettlementService::class)->ensure($ctx['moveOut'], $ctx['owner']);

        // Proposed, not applied.
        $this->assertSame(DepositTransaction::STATUS_PROPOSED, $deduction->fresh()->status);
        $this->assertSame(2000.0, (float) $settlement->proposed_deductions);
        $this->assertSame(8000.0, (float) $settlement->proposed_refund);
        $this->assertSame(10000.0, (float) $ctx['deposit']->fresh()->netRefundable());

        // Approving through the decision applies it.
        app(DepositSettlementService::class)->recordDecision($settlement, $ctx['owner'], [
            'decisions' => [$deduction->id => 'approve'],
            'approved_refund' => 8000,
        ]);

        $this->assertSame(DepositTransaction::STATUS_APPROVED, $deduction->fresh()->status);
        $this->assertSame(8000.0, (float) $ctx['deposit']->fresh()->netRefundable());
        $this->assertSame(8000.0, (float) $settlement->fresh()->approved_refund);
        $this->assertSame(2000.0, (float) $settlement->fresh()->retained_amount);
    }

    public function test_rejected_deduction_is_reversed_and_not_retained(): void
    {
        $ctx = $this->scenario();
        $deposits = app(DepositService::class);
        $deposits->recordReceipt($ctx['deposit'], ['amount' => 10000, 'verify' => true], $ctx['owner']);
        $deduction = $deposits->proposeDeduction($ctx['deposit']->fresh(), ['amount' => 3000, 'reason' => 'Stain'], $ctx['owner']);

        $settlement = app(DepositSettlementService::class)->ensure($ctx['moveOut'], $ctx['owner']);
        app(DepositSettlementService::class)->recordDecision($settlement, $ctx['owner'], [
            'decisions' => [$deduction->id => 'reject'],
            'approved_refund' => 10000,
        ]);

        $this->assertSame(DepositTransaction::STATUS_REVERSED, $deduction->fresh()->status);
        $this->assertSame(10000.0, (float) $settlement->fresh()->approved_refund);
        $this->assertSame(0.0, (float) $settlement->fresh()->retained_amount);
    }

    public function test_deduction_requiring_authorisation_must_be_confirmed(): void
    {
        $ctx = $this->scenario();
        $deposits = app(DepositService::class);
        $deposits->recordReceipt($ctx['deposit'], ['amount' => 10000, 'verify' => true], $ctx['owner']);
        $deduction = $deposits->proposeDeduction($ctx['deposit']->fresh(), ['amount' => 1500, 'reason' => 'Arrears allocation'], $ctx['owner']);
        $deduction->update(['requires_authorisation' => true]);

        $settlement = app(DepositSettlementService::class)->ensure($ctx['moveOut'], $ctx['owner']);

        $this->actingAs($ctx['owner'])
            ->post(route('landlord.settlements.decision', $settlement), [
                'decisions' => [$deduction->id => 'approve'],
                'approved_refund' => 8500,
            ])
            ->assertStatus(422);

        $this->assertSame(DepositTransaction::STATUS_PROPOSED, $deduction->fresh()->status);
    }

    public function test_tenant_dispute_is_recorded_and_survives_the_landlord_decision(): void
    {
        $ctx = $this->scenario();
        $deposits = app(DepositService::class);
        $deposits->recordReceipt($ctx['deposit'], ['amount' => 10000, 'verify' => true], $ctx['owner']);
        $deduction = $deposits->proposeDeduction($ctx['deposit']->fresh(), ['amount' => 2500, 'reason' => 'Damaged floor'], $ctx['owner']);

        $service = app(DepositSettlementService::class);
        $settlement = $service->ensure($ctx['moveOut'], $ctx['owner']);
        $service->sendToTenant($settlement, $ctx['owner']);

        $this->actingAs($ctx['user'])
            ->post(route('tenant.settlement.respond', $settlement), [
                'tenant_comment' => 'The floor was already worn when I moved in.',
                'dispute' => [$deduction->id],
            ])
            ->assertRedirect();

        $settlement->refresh();
        $this->assertSame(DepositSettlement::STATUS_DISPUTED, $settlement->status);
        $this->assertTrue($deduction->fresh()->isDisputed());
        $this->assertSame('The floor was already worn when I moved in.', $deduction->fresh()->tenant_comment);
        $this->assertSame(1, $settlement->events()->where('event', DepositSettlementEvent::EVENT_TENANT_DISPUTED)->count());

        // The landlord records a decision; the tenant's earlier response is preserved.
        $service->recordDecision($settlement, $ctx['owner'], [
            'decisions' => [$deduction->id => 'approve'],
            'approved_refund' => 7500,
            'landlord_findings' => 'Damage confirmed beyond fair wear and tear.',
        ]);

        $settlement->refresh();
        $this->assertSame('The floor was already worn when I moved in.', $settlement->tenant_comment);
        $this->assertSame(1, $settlement->events()->where('event', DepositSettlementEvent::EVENT_TENANT_RESPONDED)->count());
        $this->assertSame('Damage confirmed beyond fair wear and tear.', $settlement->landlord_findings);
    }

    public function test_settling_completes_the_refund_through_the_verified_flow(): void
    {
        $ctx = $this->scenario();
        $deposits = app(DepositService::class);
        $deposits->recordReceipt($ctx['deposit'], ['amount' => 10000, 'verify' => true], $ctx['owner']);

        $service = app(DepositSettlementService::class);
        $settlement = $service->ensure($ctx['moveOut'], $ctx['owner']);
        $service->recordDecision($settlement, $ctx['owner'], ['approved_refund' => 10000]);

        $this->actingAs($ctx['owner'])
            ->post(route('landlord.settlements.settle', $settlement), [
                'method' => 'mpesa',
                'reference' => 'REF-777',
            ])
            ->assertRedirect();

        $settlement->refresh();
        $ctx['deposit']->refresh();

        $this->assertSame(DepositSettlement::STATUS_SETTLED, $settlement->status);
        $this->assertSame(10000.0, (float) $settlement->refunded_amount);
        $this->assertSame(SecurityDeposit::STATUS_REFUNDED, $ctx['deposit']->derivedStatus());
        $this->assertTrue(
            $ctx['deposit']->transactions()->where('type', DepositTransaction::TYPE_REFUND)
                ->where('status', DepositTransaction::STATUS_COMPLETED)
                ->where('reference', 'REF-777')
                ->exists()
        );

        // A settled statement cannot be settled again.
        $this->actingAs($ctx['owner'])
            ->post(route('landlord.settlements.settle', $settlement), ['method' => 'mpesa', 'reference' => 'REF-778'])
            ->assertStatus(422);
    }

    public function test_clearance_is_blocked_until_the_settlement_is_settled(): void
    {
        $ctx = $this->scenario();

        app(DepositSettlementService::class)->ensure($ctx['moveOut'], $ctx['owner']);

        $this->actingAs($ctx['owner'])
            ->patch(route('landlord.move-outs.clear', $ctx['moveOut']), [
                'clearance_decision' => 'cleared',
                'actual_move_out_date' => now()->addMonthsNoOverflow(1)->toDateString(),
            ])
            ->assertSessionHasErrors('clearance_decision');

        $this->assertSame(MoveOutRequest::STATUS_APPROVED, $ctx['moveOut']->fresh()->status);
    }

    public function test_tenant_cannot_view_a_settlement_before_it_is_sent(): void
    {
        $ctx = $this->scenario();
        $settlement = app(DepositSettlementService::class)->ensure($ctx['moveOut'], $ctx['owner']);

        $this->actingAs($ctx['user'])
            ->get(route('tenant.settlement.show', $settlement))
            ->assertStatus(403);
    }

    public function test_another_landlord_cannot_view_the_settlement(): void
    {
        $ctx = $this->scenario();
        $settlement = app(DepositSettlementService::class)->ensure($ctx['moveOut'], $ctx['owner']);
        $other = $this->makeLandlord();

        $this->actingAs($other)
            ->get(route('landlord.settlements.show', $settlement))
            ->assertStatus(403);
    }

    public function test_comparison_flags_a_missing_move_in_baseline(): void
    {
        $ctx = $this->inspectedScenario();

        $comparison = app(MoveOutResolutionService::class)->compare($ctx['moveOut']);

        $this->assertFalse($comparison['has_baseline']);
        $this->assertNotNull($comparison['flag']);
        $this->assertNotEmpty($comparison['missing_evidence']);
    }

    public function test_comparison_matches_items_by_label_and_reports_change(): void
    {
        $ctx = $this->inspectedScenario();

        $baseline = UnitInspection::create([
            'unit_id' => $ctx['unit']->id,
            'property_id' => $ctx['property']->id,
            'tenant_id' => $ctx['tenant']->id,
            'type' => UnitInspection::TYPE_MOVE_IN,
            'status' => UnitInspection::STATUS_SUBMITTED,
            'inspection_date' => now()->subMonths(6)->toDateString(),
        ]);

        $baseline->items()->create([
            'category' => $ctx['firstItem']->category,
            'label' => $ctx['firstItem']->label,
            'condition' => 'good',
        ]);

        $comparison = app(MoveOutResolutionService::class)->compare($ctx['moveOut']);

        $this->assertTrue($comparison['has_baseline']);

        $row = collect($comparison['rows'])->firstWhere('label', $ctx['firstItem']->label);

        $this->assertNotNull($row);
        $this->assertTrue($row['changed']);
        $this->assertFalse($row['missing_baseline']);
        $this->assertSame('new_damage', $row['nature']);
        $this->assertSame(1, $comparison['new_damage_count']);
    }
}
