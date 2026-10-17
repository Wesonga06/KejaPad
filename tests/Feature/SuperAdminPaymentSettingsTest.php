<?php

namespace Tests\Feature;

use App\Models\MpesaPayment;
use App\Models\Property;
use App\Models\RentRecord;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Rules\PaymentMethodEnabled;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SuperAdminPaymentSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->superAdmin()->create();
    }

    public function test_index_renders(): void
    {
        $this->actingAs($this->admin())
            ->get(route('super_admin.payments.index'))
            ->assertOk()
            ->assertSee('Payment Settings');
    }

    public function test_update_persists_methods_and_flags(): void
    {
        $this->actingAs($this->admin())->post(route('super_admin.payments.update'), [
            'methods' => ['mpesa', 'cash'],
            'require_verification' => '1',
            'callback_enforce_ip' => '1',
            'callback_allowed_ips' => '197.248.0.1, 196.201.214.200',
        ])->assertRedirect(route('super_admin.payments.index'));

        $this->assertDatabaseHas('settings', ['key' => 'payments.require_verification', 'value' => '1']);

        app(SettingsService::class)->applyToConfig();

        $this->assertSame(['mpesa', 'cash'], config('payments.methods_enabled'));
        $this->assertSame(['197.248.0.1', '196.201.214.200'], config('mpesa.callback_allowed_ips'));
    }

    public function test_requires_at_least_one_payment_method(): void
    {
        $this->actingAs($this->admin())->post(route('super_admin.payments.update'), [
            'methods' => [],
            'require_verification' => '0',
            'callback_enforce_ip' => '1',
        ])->assertSessionHasErrors('methods');
    }

    public function test_disabled_method_is_rejected_by_rule(): void
    {
        config(['payments.methods_enabled' => ['cash']]);

        $validator = Validator::make(['method' => 'mpesa'], [
            'method' => [new PaymentMethodEnabled],
        ]);

        $this->assertTrue($validator->fails());

        $validator = Validator::make(['method' => 'cash'], [
            'method' => [new PaymentMethodEnabled],
        ]);

        $this->assertFalse($validator->fails());
    }

    public function test_callback_defers_credit_when_verification_required(): void
    {
        app(SettingsService::class)->update(['payments.require_verification' => true]);
        app(SettingsService::class)->applyToConfig();
        config(['mpesa.callback_enforce_ip' => false]);

        $payment = $this->pendingPayment();

        $this->postJson('/api/mpesa/callback', $this->successPayload())->assertOk();

        $payment->refresh();

        $this->assertSame('pending', $payment->status);
        $this->assertSame('RECEIPT123', $payment->mpesa_receipt_number);
        $this->assertSame('0.00', (string) $payment->rentRecord->amount_paid);
        $this->assertDatabaseMissing('payments', ['mpesa_payment_id' => $payment->id]);
    }

    public function test_transactions_page_and_export(): void
    {
        $admin = $this->admin();
        $this->pendingPayment();

        $this->actingAs($admin)->get(route('super_admin.payments.transactions'))->assertOk();

        $response = $this->actingAs($admin)->get(route('super_admin.payments.export'));
        $response->assertOk();

        $this->assertStringContainsString('Receipt', $response->streamedContent());
    }

    public function test_landlord_cannot_manage_payment_settings(): void
    {
        $landlord = User::factory()->landlord()->create();

        $this->actingAs($landlord)->get(route('super_admin.payments.index'))->assertForbidden();
    }

    private function pendingPayment(): MpesaPayment
    {
        $landlord = User::factory()->landlord()->create();

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
}
