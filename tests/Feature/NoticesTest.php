<?php

namespace Tests\Feature;

use App\Models\RentNotice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesRentScenario;
use Tests\TestCase;

class NoticesTest extends TestCase
{
    use CreatesRentScenario;
    use RefreshDatabase;

    public function test_landlord_notices_index_renders(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $unit = $this->unit($property);
        $tenant = $this->tenant($property, $unit, null, 'Alice Notice');

        RentNotice::create([
            'tenant_id' => $tenant->id,
            'type' => 'payment_reminder',
            'stage' => 1,
            'issued_at' => now()->toDateString(),
            'recipient' => $tenant->phone,
            'delivery_status' => 'sent',
            'created_by' => $landlord->id,
        ]);

        $this->actingAs($landlord)
            ->get(route('landlord.notices.index'))
            ->assertOk()
            ->assertSee('Alice Notice')
            ->assertSee('Payment Reminder');
    }

    public function test_landlord_notices_create_page_renders(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $unit = $this->unit($property);
        $this->tenant($property, $unit);

        $this->actingAs($landlord)
            ->get(route('landlord.notices.create'))
            ->assertOk()
            ->assertSee('Record Notice');
    }

    public function test_notices_index_is_scoped_to_the_landlords_own_tenants(): void
    {
        $owner = $this->landlord();
        $other = $this->landlord();

        $property = $this->property($owner);
        $unit = $this->unit($property);
        $tenant = $this->tenant($property, $unit, null, 'Own Tenant');

        $otherProperty = $this->property($other);
        $otherUnit = $this->unit($otherProperty);
        $otherTenant = $this->tenant($otherProperty, $otherUnit, null, 'Foreign Tenant');

        foreach ([$tenant, $otherTenant] as $t) {
            RentNotice::create([
                'tenant_id' => $t->id,
                'type' => 'escalation',
                'stage' => 3,
                'issued_at' => now()->toDateString(),
                'delivery_status' => 'sent',
                'created_by' => $t->property->user_id,
            ]);
        }

        $this->actingAs($owner)
            ->get(route('landlord.notices.index'))
            ->assertOk()
            ->assertSee('Own Tenant')
            ->assertDontSee('Foreign Tenant');
    }

    public function test_tenant_cannot_access_landlord_notices(): void
    {
        $user = $this->tenantUser();

        $this->actingAs($user)
            ->get(route('landlord.notices.index'))
            ->assertForbidden();
    }
}
