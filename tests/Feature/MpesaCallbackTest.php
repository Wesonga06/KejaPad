<?php

namespace Tests\Feature;

use App\Models\MpesaPayment;
use App\Models\Property;
use App\Models\RentRecord;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MpesaCallbackTest extends TestCase
{
    use RefreshDatabase;

    private function pendingPayment(): MpesaPayment
    {
        $landlord = User::factory()->create(['role' => 'landlord']);

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
            'property_id' => $property->id,
            'unit_id' => $unit->id,
            'full_name' => 'Jane Doe',
            'phone' => '254712345678',
            'move_in_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $rent = RentRecord::create([
            'tenant_id' => $tenant->id,
            'property_id' => $property->id,
            'unit_id' => $unit->id,
            'amount_due' => 10000,
            'amount_paid' => 0,
            'due_date' => now()->toDateString(),
            'status' => 'pending',
            'month' => now()->month,
            'year' => now()->year,
        ]);

        return MpesaPayment::create([
            'tenant_id' => $tenant->id,
            'rent_record_id' => $rent->id,
            'phone_number' => '254712345678',
            'amount' => 10000,
            'checkout_request_id' => 'CHECKOUT-1',
            'status' => 'pending',
        ]);
    }

    private function successPayload(): array
    {
        return [
            'Body' => [
                'stkCallback' => [
                    'CheckoutRequestID' => 'CHECKOUT-1',
                    'ResultCode' => 0,
                    'ResultDesc' => 'The service request is processed successfully.',
                    'CallbackMetadata' => [
                        'Item' => [
                            ['Name' => 'Amount', 'Value' => 10000],
                            ['Name' => 'MpesaReceiptNumber', 'Value' => 'RECEIPT123'],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function test_callback_from_disallowed_ip_is_rejected(): void
    {
        config(['mpesa.callback_enforce_ip' => true]);
        $this->pendingPayment();

        $this->postJson('/api/mpesa/callback', $this->successPayload())
            ->assertStatus(403);

        $this->assertDatabaseHas('mpesa_payments', [
            'checkout_request_id' => 'CHECKOUT-1',
            'status' => 'pending',
        ]);
    }

    public function test_callback_is_rejected_when_ip_enforcement_has_no_allowlist(): void
    {
        config([
            'mpesa.callback_enforce_ip' => true,
            'mpesa.callback_allowed_ips' => [],
        ]);
        $this->pendingPayment();

        $this->postJson('/api/mpesa/callback', $this->successPayload())
            ->assertStatus(403);

        $this->assertDatabaseHas('mpesa_payments', [
            'checkout_request_id' => 'CHECKOUT-1',
            'status' => 'pending',
        ]);
    }

    public function test_successful_callback_confirms_and_credits_rent(): void
    {
        config(['mpesa.callback_enforce_ip' => false]);
        $payment = $this->pendingPayment();

        $this->postJson('/api/mpesa/callback', $this->successPayload())
            ->assertOk();

        $this->assertSame('confirmed', $payment->fresh()->status);
        $this->assertSame('RECEIPT123', $payment->fresh()->mpesa_receipt_number);
        $this->assertSame('10000.00', (string) $payment->fresh()->rentRecord->amount_paid);
        $this->assertSame('paid', $payment->fresh()->rentRecord->status);
    }

    public function test_duplicate_callback_does_not_double_credit(): void
    {
        config(['mpesa.callback_enforce_ip' => false]);
        $payment = $this->pendingPayment();

        $this->postJson('/api/mpesa/callback', $this->successPayload())->assertOk();
        $this->postJson('/api/mpesa/callback', $this->successPayload())->assertOk();

        $this->assertSame('10000.00', (string) $payment->fresh()->rentRecord->amount_paid);
    }

    public function test_success_callback_with_short_amount_is_not_confirmed_or_credited(): void
    {
        config(['mpesa.callback_enforce_ip' => false]);
        $payment = $this->pendingPayment();
        $payload = $this->successPayload();
        $payload['Body']['stkCallback']['CallbackMetadata']['Item'][0]['Value'] = 9999;

        $this->postJson('/api/mpesa/callback', $payload)->assertOk();

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertStringContainsString('manual review', $payment->fresh()->result_desc);
        $this->assertSame('0.00', (string) $payment->fresh()->rentRecord->amount_paid);
    }

    public function test_success_callback_without_amount_is_not_confirmed_or_credited(): void
    {
        config(['mpesa.callback_enforce_ip' => false]);
        $payment = $this->pendingPayment();
        $payload = $this->successPayload();
        $payload['Body']['stkCallback']['CallbackMetadata']['Item'] = array_values(array_filter(
            $payload['Body']['stkCallback']['CallbackMetadata']['Item'],
            fn (array $item): bool => $item['Name'] !== 'Amount',
        ));

        $this->postJson('/api/mpesa/callback', $payload)->assertOk();

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertStringContainsString('manual review', $payment->fresh()->result_desc);
        $this->assertSame('0.00', (string) $payment->fresh()->rentRecord->amount_paid);
    }

    public function test_landlord_cannot_manually_confirm_a_failed_payment(): void
    {
        $payment = $this->pendingPayment();
        $payment->update(['status' => 'failed']);

        $this->actingAs($payment->tenant->property->landlord)
            ->post(route('landlord.mpesa-payments.confirm', $payment))
            ->assertSessionHasErrors('payment');

        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertDatabaseCount('payments', 0);
        $this->assertSame('0.00', (string) $payment->rentRecord->fresh()->amount_paid);
    }

    public function test_landlord_cannot_reject_a_payment_after_it_was_confirmed(): void
    {
        $payment = $this->pendingPayment();
        $payment->update(['status' => 'confirmed']);

        $this->actingAs($payment->tenant->property->landlord)
            ->post(route('landlord.mpesa-payments.reject', $payment))
            ->assertSessionHasErrors('payment');

        $this->assertSame('confirmed', $payment->fresh()->status);
    }

    public function test_callback_cannot_credit_a_cancelled_payment(): void
    {
        config(['mpesa.callback_enforce_ip' => false]);
        $payment = $this->pendingPayment();
        $payment->update(['status' => 'cancelled']);

        $this->postJson('/api/mpesa/callback', $this->successPayload())
            ->assertOk();

        $this->assertSame('cancelled', $payment->fresh()->status);
        $this->assertDatabaseCount('payments', 0);
        $this->assertSame('0.00', (string) $payment->rentRecord->fresh()->amount_paid);
    }
}
