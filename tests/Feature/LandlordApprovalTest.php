<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\LandlordApprovedNotification;
use App\Notifications\LandlordRejectedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LandlordApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function superAdmin(): User
    {
        return User::factory()->superAdmin()->create();
    }

    public function test_new_landlord_registration_is_pending_approval(): void
    {
        $response = $this->post('/register', [
            'name' => 'New Landlord',
            'email' => 'newlandlord@example.com',
            'role' => 'landlord',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));

        $user = User::where('email', 'newlandlord@example.com')->firstOrFail();

        $this->assertSame(User::STATUS_PENDING, $user->status);
        $this->assertDatabaseHas('landlord_approval_actions', [
            'user_id' => $user->id,
            'action' => 'submitted',
        ]);
    }

    public function test_pending_landlord_cannot_access_landlord_dashboard(): void
    {
        $landlord = User::factory()->pendingLandlord()->create();

        $this->actingAs($landlord)
            ->get(route('landlord.dashboard'))
            ->assertRedirect(route('landlord.pending'));

        $this->actingAs($landlord)
            ->get(route('landlord.pending'))
            ->assertOk()
            ->assertSee('under review');
    }

    public function test_approved_landlord_can_access_dashboard(): void
    {
        $landlord = User::factory()->landlord()->create();

        $this->actingAs($landlord)
            ->get(route('landlord.dashboard'))
            ->assertOk();
    }

    public function test_rejected_and_suspended_landlords_are_blocked(): void
    {
        $rejected = User::factory()->pendingLandlord()->create(['status' => User::STATUS_REJECTED]);
        $suspended = User::factory()->landlord()->create(['status' => User::STATUS_SUSPENDED]);

        $this->actingAs($rejected)->get(route('landlord.dashboard'))->assertRedirect(route('landlord.pending'));
        $this->actingAs($suspended)->get(route('landlord.dashboard'))->assertRedirect(route('landlord.pending'));
    }

    public function test_super_admin_can_approve_an_application(): void
    {
        Notification::fake();

        $admin = $this->superAdmin();
        $landlord = User::factory()->pendingLandlord()->create();

        $this->actingAs($admin)
            ->post(route('super_admin.landlord-applications.approve', $landlord))
            ->assertRedirect(route('super_admin.landlord-applications.show', $landlord));

        $fresh = $landlord->fresh();
        $this->assertSame(User::STATUS_APPROVED, $fresh->status);
        $this->assertNotNull($fresh->approved_at);
        $this->assertSame($admin->id, $fresh->approved_by);
        $this->assertDatabaseHas('landlord_approval_actions', [
            'user_id' => $landlord->id,
            'action' => 'approved',
            'performed_by' => $admin->id,
        ]);

        Notification::assertSentTo($landlord, LandlordApprovedNotification::class);

        $this->actingAs($fresh)->get(route('landlord.dashboard'))->assertOk();
    }

    public function test_rejecting_an_application_requires_a_reason(): void
    {
        $admin = $this->superAdmin();
        $landlord = User::factory()->pendingLandlord()->create();

        $this->actingAs($admin)
            ->post(route('super_admin.landlord-applications.reject', $landlord), [])
            ->assertSessionHasErrors('reason');

        $this->assertSame(User::STATUS_PENDING, $landlord->fresh()->status);
    }

    public function test_super_admin_can_reject_with_a_reason(): void
    {
        Notification::fake();

        $admin = $this->superAdmin();
        $landlord = User::factory()->pendingLandlord()->create();

        $this->actingAs($admin)
            ->post(route('super_admin.landlord-applications.reject', $landlord), [
                'reason' => 'Incomplete ownership documents.',
            ])
            ->assertRedirect();

        $fresh = $landlord->fresh();
        $this->assertSame(User::STATUS_REJECTED, $fresh->status);
        $this->assertSame('Incomplete ownership documents.', $fresh->approval_reason);
        $this->assertNotNull($fresh->rejected_at);
        $this->assertDatabaseHas('landlord_approval_actions', [
            'user_id' => $landlord->id,
            'action' => 'rejected',
            'reason' => 'Incomplete ownership documents.',
        ]);

        Notification::assertSentTo($landlord, LandlordRejectedNotification::class);
    }

    public function test_super_admin_can_suspend_and_reinstate(): void
    {
        Notification::fake();

        $admin = $this->superAdmin();
        $landlord = User::factory()->landlord()->create();

        $this->actingAs($admin)
            ->post(route('super_admin.landlord-applications.suspend', $landlord), ['reason' => 'Policy breach'])
            ->assertRedirect();

        $this->assertSame(User::STATUS_SUSPENDED, $landlord->fresh()->status);
        $this->actingAs($landlord->fresh())->get(route('landlord.dashboard'))
            ->assertRedirect(route('landlord.pending'));

        $this->actingAs($admin)
            ->post(route('super_admin.landlord-applications.reinstate', $landlord))
            ->assertRedirect();

        $this->assertSame(User::STATUS_APPROVED, $landlord->fresh()->status);
    }

    public function test_landlord_cannot_access_the_applications_admin(): void
    {
        $landlord = User::factory()->landlord()->create();
        $application = User::factory()->pendingLandlord()->create();

        $this->actingAs($landlord)
            ->get(route('super_admin.landlord-applications.index'))
            ->assertForbidden();

        $this->actingAs($landlord)
            ->post(route('super_admin.landlord-applications.approve', $application))
            ->assertForbidden();

        $this->assertSame(User::STATUS_PENDING, $application->fresh()->status);
    }

    public function test_super_admin_can_view_application_details(): void
    {
        $admin = $this->superAdmin();
        $landlord = User::factory()->pendingLandlord()->create(['name' => 'Grace Mwangi']);

        $this->actingAs($admin)
            ->get(route('super_admin.landlord-applications.show', $landlord))
            ->assertOk()
            ->assertSee('Grace Mwangi');
    }
}
