<?php

namespace Tests\Feature;

use App\Models\MoveOutInspection;
use App\Models\MoveOutInspectionItem;
use App\Models\MoveOutRequest;
use App\Models\Tenant;
use App\Models\UnitConditionAssessment;
use App\Models\UnitDocument;
use App\Models\UnitInspection;
use App\Models\UnitInspectionItem;
use App\Models\User;
use App\Services\UnitPassportService;
use App\Services\UnitTimelineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\CreatesMoveOutScenario;
use Tests\TestCase;

class PropertyPassportTest extends TestCase
{
    use CreatesMoveOutScenario;
    use RefreshDatabase;

    // ---- Access control -------------------------------------------------

    public function test_landlord_can_view_passport_index_and_show(): void
    {
        $landlord = $this->makeLandlord();
        $property = $this->makeProperty($landlord);
        $unit = $this->makeUnit($property);

        $this->actingAs($landlord)
            ->get(route('landlord.passport.index', $property))
            ->assertOk();

        $this->actingAs($landlord)
            ->get(route('landlord.passport.show', [$property, $unit]))
            ->assertOk()
            ->assertSee($unit->passportCode());
    }

    public function test_other_landlord_cannot_view_unit_passport(): void
    {
        $owner = $this->makeLandlord();
        $property = $this->makeProperty($owner);
        $unit = $this->makeUnit($property);

        $intruder = $this->makeLandlord();
        $this->makeProperty($intruder);

        $this->actingAs($intruder)
            ->get(route('landlord.passport.show', [$property, $unit]))
            ->assertForbidden();
    }

    public function test_assigned_caretaker_can_view_but_unassigned_caretaker_cannot(): void
    {
        $landlord = $this->makeLandlord();
        $property = $this->makeProperty($landlord);
        $unit = $this->makeUnit($property);

        $assigned = $this->makeCaretaker();
        $property->caretakers()->attach($assigned->id);

        $this->actingAs($assigned)
            ->get(route('caretaker.passport.show', $unit))
            ->assertOk();

        $outsider = $this->makeCaretaker();
        $this->actingAs($outsider)
            ->get(route('caretaker.passport.show', $unit))
            ->assertForbidden();
    }

    public function test_tenant_can_view_own_passport(): void
    {
        $landlord = $this->makeLandlord();
        $property = $this->makeProperty($landlord);
        $unit = $this->makeUnit($property);

        $tenantUser = $this->makeTenantUser();
        $this->makeTenant($property, $unit, $tenantUser, 'Current Tenant');

        $this->actingAs($tenantUser)
            ->get(route('tenant.passport.show'))
            ->assertOk()
            ->assertSee($unit->passportCode());
    }

    // ---- Inspection lifecycle -------------------------------------------

    public function test_inspection_lifecycle_creates_items_and_records_history(): void
    {
        $landlord = $this->makeLandlord();
        $property = $this->makeProperty($landlord);
        $unit = $this->makeUnit($property);

        $this->actingAs($landlord)
            ->post(route('landlord.passport.inspections.store', [$property, $unit]), [
                'type' => UnitInspection::TYPE_MOVE_IN,
                'inspection_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $inspection = UnitInspection::firstOrFail();
        $this->assertSame(UnitInspection::STATUS_DRAFT, $inspection->status);
        $this->assertGreaterThan(0, $inspection->items()->count());
        // Utility meter items are skipped in the generic checklist.
        $this->assertSame(0, $inspection->items()->where('category', 'utilities')->count());

        $items = $inspection->items->mapWithKeys(fn (UnitInspectionItem $item) => [
            $item->id => ['condition' => MoveOutInspection::CONDITION_GOOD, 'comment' => 'OK'],
        ])->all();

        $this->actingAs($landlord)
            ->post(route('landlord.passport.inspections.submit', $inspection), [
                'inspection_date' => now()->toDateString(),
                'cleanliness' => 'clean',
                'keys_returned' => 1,
                'recommendation' => UnitInspection::RECOMMEND_CLEARANCE,
                'items' => $items,
            ])
            ->assertRedirect();

        $inspection->refresh();
        $this->assertSame(UnitInspection::STATUS_SUBMITTED, $inspection->status);
        $this->assertSame(MoveOutInspection::CONDITION_GOOD, $inspection->items()->first()->condition);
        $this->assertDatabaseHas('audit_logs', ['action' => 'unit_inspection_submitted']);
    }

    public function test_tenant_cannot_create_inspection(): void
    {
        $landlord = $this->makeLandlord();
        $property = $this->makeProperty($landlord);
        $unit = $this->makeUnit($property);

        $tenantUser = $this->makeTenantUser();
        $this->makeTenant($property, $unit, $tenantUser);

        $this->actingAs($tenantUser)
            ->post(route('landlord.passport.inspections.store', [$property, $unit]), [
                'type' => UnitInspection::TYPE_ROUTINE,
            ])
            ->assertForbidden();

        $this->assertSame(0, UnitInspection::count());
    }

    // ---- Condition assessments ------------------------------------------

    public function test_only_landlord_can_record_condition_assessment(): void
    {
        $landlord = $this->makeLandlord();
        $property = $this->makeProperty($landlord);
        $unit = $this->makeUnit($property);

        $this->actingAs($landlord)
            ->post(route('landlord.passport.conditions.store', [$property, $unit]), [
                'assessed_at' => now()->toDateString(),
                'rating' => UnitConditionAssessment::RATING_GOOD,
                'summary' => 'Unit is in good shape.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('unit_condition_assessments', [
            'unit_id' => $unit->id,
            'rating' => UnitConditionAssessment::RATING_GOOD,
        ]);

        $caretaker = $this->makeCaretaker();
        $property->caretakers()->attach($caretaker->id);

        $this->actingAs($caretaker)
            ->post(route('landlord.passport.conditions.store', [$property, $unit]), [
                'summary' => 'Should not be allowed.',
            ])
            ->assertForbidden();
    }

    // ---- Documents & privacy --------------------------------------------

    public function test_documents_respect_visibility_and_permissions(): void
    {
        Storage::fake('local');

        $landlord = $this->makeLandlord();
        $property = $this->makeProperty($landlord);
        $unit = $this->makeUnit($property);

        $tenantUser = $this->makeTenantUser();
        $this->makeTenant($property, $unit, $tenantUser, 'Current Tenant');

        // A private document and a tenant-visible one.
        $this->actingAs($landlord)
            ->post(route('landlord.passport.documents.store', [$property, $unit]), [
                'title' => 'Private Invoice',
                'type' => UnitDocument::TYPE_INVOICE,
                'visibility' => UnitDocument::VISIBILITY_LANDLORD,
                'file' => UploadedFile::fake()->create('invoice.pdf', 20, 'application/pdf'),
            ])
            ->assertRedirect();

        $this->actingAs($landlord)
            ->post(route('landlord.passport.documents.store', [$property, $unit]), [
                'title' => 'Shared Handover',
                'type' => UnitDocument::TYPE_HANDOVER,
                'visibility' => UnitDocument::VISIBILITY_TENANT,
                'file' => UploadedFile::fake()->create('handover.pdf', 20, 'application/pdf'),
            ])
            ->assertRedirect();

        $private = UnitDocument::where('title', 'Private Invoice')->firstOrFail();
        $shared = UnitDocument::where('title', 'Shared Handover')->firstOrFail();

        // The tenant may only download the shared document.
        $this->actingAs($tenantUser)
            ->get(route('passport.documents.download', $shared))
            ->assertOk();

        $this->actingAs($tenantUser)
            ->get(route('passport.documents.download', $private))
            ->assertForbidden();

        // The tenant does not see the private document anywhere on the passport.
        $this->actingAs($tenantUser)
            ->get(route('tenant.passport.show'))
            ->assertOk()
            ->assertSee('Shared Handover')
            ->assertDontSee('Private Invoice');
    }

    public function test_tenant_timeline_excludes_other_tenants_and_internal_records(): void
    {
        $landlord = $this->makeLandlord();
        $property = $this->makeProperty($landlord);
        $unit = $this->makeUnit($property);

        // A previous tenancy that must not be visible to the current tenant.
        Tenant::create([
            'user_id' => null,
            'property_id' => $property->id,
            'unit_id' => $unit->id,
            'full_name' => 'Previous Person',
            'phone' => '254700000000',
            'move_in_date' => now()->subYears(2)->toDateString(),
            'move_out_date' => now()->subYear()->toDateString(),
            'is_active' => false,
        ]);

        $tenantUser = $this->makeTenantUser();
        $tenant = $this->makeTenant($property, $unit, $tenantUser, 'Current Tenant');

        // An internal condition assessment must not surface to the tenant.
        UnitConditionAssessment::create([
            'unit_id' => $unit->id,
            'property_id' => $property->id,
            'assessed_by' => $landlord->id,
            'created_by' => $landlord->id,
            'assessed_at' => now()->toDateString(),
            'summary' => 'InternalSecretNote',
        ]);

        $this->actingAs($tenantUser)
            ->get(route('tenant.passport.show'))
            ->assertOk()
            ->assertDontSee('Previous Person')
            ->assertDontSee('InternalSecretNote');

        // Sanity check the service filtering directly.
        $events = app(UnitTimelineService::class)->build($unit, [], $tenantUser);
        $this->assertFalse($events->contains(fn ($e) => str_contains((string) $e['description'], 'Previous Person')));
        $this->assertFalse($events->contains(fn ($e) => $e['category'] === UnitTimelineService::CATEGORY_CONDITION));
    }

    public function test_tenant_can_acknowledge_submitted_inspection(): void
    {
        $landlord = $this->makeLandlord();
        $property = $this->makeProperty($landlord);
        $unit = $this->makeUnit($property);

        $tenantUser = $this->makeTenantUser();
        $tenant = $this->makeTenant($property, $unit, $tenantUser, 'Current Tenant');

        $inspection = UnitInspection::create([
            'unit_id' => $unit->id,
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
            'type' => UnitInspection::TYPE_MOVE_IN,
            'status' => UnitInspection::STATUS_SUBMITTED,
            'inspection_date' => now()->toDateString(),
            'inspected_by' => $landlord->id,
            'created_by' => $landlord->id,
        ]);

        $this->actingAs($tenantUser)
            ->post(route('tenant.passport.inspections.acknowledge', $inspection), [
                'acknowledge' => 1,
                'acknowledgement_name' => 'Current Tenant',
            ])
            ->assertRedirect();

        $inspection->refresh();
        $this->assertNotNull($inspection->acknowledged_at);
        $this->assertSame($tenantUser->id, $inspection->acknowledged_by);
    }

    // ---- Persistence & comparison ---------------------------------------

    public function test_move_in_inspection_remains_available_after_move_out(): void
    {
        $landlord = $this->makeLandlord();
        $property = $this->makeProperty($landlord);
        $unit = $this->makeUnit($property);
        $tenant = $this->makeTenant($property, $unit, null, 'Past Tenant');

        $moveIn = UnitInspection::create([
            'unit_id' => $unit->id,
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
            'type' => UnitInspection::TYPE_MOVE_IN,
            'status' => UnitInspection::STATUS_SUBMITTED,
            'inspection_date' => now()->subYears(1)->toDateString(),
            'inspected_by' => $landlord->id,
            'created_by' => $landlord->id,
        ]);

        $item = $moveIn->items()->create([
            'category' => 'walls',
            'label' => 'Flooring',
            'condition' => MoveOutInspection::CONDITION_GOOD,
        ]);

        // The tenancy ends.
        $tenant->update(['is_active' => false, 'move_out_date' => now()->toDateString()]);

        $request = MoveOutRequest::create([
            'tenant_id' => $tenant->id,
            'property_id' => $property->id,
            'unit_id' => $unit->id,
            'status' => MoveOutRequest::STATUS_COMPLETED,
            'submitted_at' => now()->subMonth(),
            'proposed_move_out_date' => now()->toDateString(),
        ]);

        $moveOut = MoveOutInspection::create([
            'move_out_request_id' => $request->id,
            'caretaker_id' => null,
            'status' => MoveOutInspection::STATUS_SUBMITTED,
            'completed_at' => now(),
        ]);

        MoveOutInspectionItem::create([
            'move_out_inspection_id' => $moveOut->id,
            'category' => 'walls',
            'label' => $item->label,
            'condition' => MoveOutInspection::CONDITION_DAMAGED,
        ]);

        $comparison = app(UnitPassportService::class)->moveInMoveOutComparison($unit);

        $this->assertNotEmpty($comparison);
        $changed = collect($comparison)->firstWhere('label', 'Flooring');
        $this->assertNotNull($changed);
        $this->assertTrue($changed['changed']);
    }

    public function test_inspection_editor_renders_for_landlord_and_caretaker(): void
    {
        $landlord = $this->makeLandlord();
        $property = $this->makeProperty($landlord);
        $unit = $this->makeUnit($property);

        $this->actingAs($landlord)
            ->post(route('landlord.passport.inspections.store', [$property, $unit]), [
                'type' => UnitInspection::TYPE_ROUTINE,
                'inspection_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $inspection = UnitInspection::firstOrFail();

        $this->actingAs($landlord)
            ->get(route('landlord.passport.inspections.show', $inspection))
            ->assertOk()
            ->assertSee('General cleanliness');

        $caretaker = $this->makeCaretaker();
        $property->caretakers()->attach($caretaker->id);

        $this->actingAs($caretaker)
            ->get(route('caretaker.passport.inspections.show', $inspection))
            ->assertOk();
    }

    // ---- Oversight ------------------------------------------------------

    public function test_super_admin_can_view_passport_oversight(): void
    {
        $landlord = $this->makeLandlord();
        $property = $this->makeProperty($landlord);
        $unit = $this->makeUnit($property);

        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin)
            ->get(route('super_admin.passports.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('super_admin.passports.show', $unit))
            ->assertOk()
            ->assertSee($unit->passportCode());
    }

    public function test_caretaker_passport_pages_render(): void
    {
        $landlord = $this->makeLandlord();
        $property = $this->makeProperty($landlord);
        $unit = $this->makeUnit($property);

        $caretaker = $this->makeCaretaker();
        $property->caretakers()->attach($caretaker->id);

        $this->actingAs($caretaker)->get(route('caretaker.passport.index'))->assertOk();
        $this->actingAs($caretaker)->get(route('caretaker.passport.show', $unit))->assertOk();
    }
}
