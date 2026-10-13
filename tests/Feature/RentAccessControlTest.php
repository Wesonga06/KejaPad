<?php

namespace Tests\Feature;

use App\Models\RentNotice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesRentScenario;
use Tests\TestCase;

class RentAccessControlTest extends TestCase
{
    use CreatesRentScenario;
    use RefreshDatabase;

    public function test_landlord_cannot_record_a_payment_for_another_landlords_tenant(): void
    {
        $owner = $this->landlord();
        $stranger = $this->landlord();
        $property = $this->property($owner);
        $unit = $this->unit($property, 10000);
        $tenant = $this->tenant($property, $unit);
        $this->rent($tenant, $property, $unit, now()->month, now()->year, 10000, now()->toDateString());

        $response = $this->actingAs($stranger)->post(route('landlord.tenants.payments.store', $tenant), [
            'amount_paid' => 5000,
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_landlord_cannot_issue_a_notice_for_another_landlords_tenant(): void
    {
        $owner = $this->landlord();
        $stranger = $this->landlord();
        $property = $this->property($owner);
        $unit = $this->unit($property, 10000);
        $tenant = $this->tenant($property, $unit);

        $response = $this->actingAs($stranger)->post(route('landlord.tenants.notices.store', $tenant), [
            'type' => 'formal_notice',
            'issued_at' => now()->toDateString(),
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('rent_notices', 0);
    }

    public function test_landlord_cannot_update_another_landlords_notice(): void
    {
        $owner = $this->landlord();
        $stranger = $this->landlord();
        $property = $this->property($owner);
        $unit = $this->unit($property, 10000);
        $tenant = $this->tenant($property, $unit);

        $notice = RentNotice::create([
            'tenant_id' => $tenant->id,
            'type' => 'formal_notice',
            'stage' => 4,
            'issued_at' => now()->toDateString(),
            'delivery_status' => 'sent',
        ]);

        $response = $this->actingAs($stranger)->patch(route('landlord.notices.update', $notice), [
            'delivery_status' => 'acknowledged',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('rent_notices', ['id' => $notice->id, 'delivery_status' => 'sent']);
    }

    public function test_tenant_role_cannot_access_the_arrears_overview(): void
    {
        $response = $this->actingAs($this->tenantUser())->get(route('landlord.arrears.index'));

        $response->assertForbidden();
    }

    public function test_owner_can_record_a_payment_and_issue_a_notice(): void
    {
        $owner = $this->landlord();
        $property = $this->property($owner);
        $unit = $this->unit($property, 10000);
        $tenant = $this->tenant($property, $unit);
        $rent = $this->rent($tenant, $property, $unit, now()->month, now()->year, 10000, now()->toDateString());

        $this->actingAs($owner)->post(route('landlord.tenants.payments.store', $tenant), [
            'amount_paid' => 5000,
            'payment_date' => now()->toDateString(),
            'method' => 'cash',
        ])->assertRedirect();

        $this->assertDatabaseCount('payments', 1);
        $this->assertSame('5000.00', (string) $rent->fresh()->amount_paid);

        $this->actingAs($owner)->post(route('landlord.tenants.notices.store', $tenant), [
            'type' => 'formal_notice',
            'issued_at' => now()->toDateString(),
            'delivery_status' => 'sent',
        ])->assertRedirect();

        $this->assertDatabaseHas('rent_notices', ['tenant_id' => $tenant->id, 'type' => 'formal_notice', 'stage' => 4]);
    }

    public function test_owner_can_update_notice_delivery_status(): void
    {
        $owner = $this->landlord();
        $property = $this->property($owner);
        $unit = $this->unit($property, 10000);
        $tenant = $this->tenant($property, $unit);

        $notice = RentNotice::create([
            'tenant_id' => $tenant->id,
            'type' => 'formal_notice',
            'stage' => 4,
            'issued_at' => now()->toDateString(),
            'delivery_status' => 'sent',
        ]);

        $this->actingAs($owner)->patch(route('landlord.notices.update', $notice), [
            'delivery_status' => 'acknowledged',
        ])->assertRedirect();

        $this->assertSame('acknowledged', $notice->fresh()->delivery_status);
    }
}
