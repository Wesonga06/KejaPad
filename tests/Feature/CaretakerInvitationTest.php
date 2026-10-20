<?php

namespace Tests\Feature;

use App\Models\CaretakerInvitation;
use App\Models\Property;
use App\Models\User;
use App\Services\CaretakerInvitationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesRentScenario;
use Tests\TestCase;

class CaretakerInvitationTest extends TestCase
{
    use CreatesRentScenario, RefreshDatabase;

    protected function service(): CaretakerInvitationService
    {
        return app(CaretakerInvitationService::class);
    }

    protected function makeInvitation(User $landlord, ?Property $property = null, array $overrides = []): CaretakerInvitation
    {
        $property ??= $this->property($landlord);

        $invitation = $this->service()->create($landlord, [
            'property_id' => $property->id,
            'email' => $overrides['email'] ?? null,
            'name' => $overrides['name'] ?? null,
        ]);

        $state = array_diff_key($overrides, ['email' => true, 'name' => true]);
        if ($state !== []) {
            $invitation->update($state);
        }

        return $invitation->fresh();
    }

    public function test_landlord_can_create_an_invitation_for_their_property(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord);

        $this->actingAs($landlord)
            ->post(route('landlord.caretaker-invitations.store'), [
                'property_id' => $property->id,
                'name' => 'Mary Wanjiku',
                'email' => 'mary@example.com',
            ])
            ->assertRedirect(route('landlord.caretaker-invitations.index'));

        $invitation = CaretakerInvitation::firstOrFail();

        $this->assertSame(CaretakerInvitation::STATUS_PENDING, $invitation->status);
        $this->assertSame($landlord->id, $invitation->landlord_id);
        $this->assertSame($property->id, $invitation->property_id);
        $this->assertNotEmpty($invitation->code);
        $this->assertSame(CaretakerInvitation::hashCode($invitation->code), $invitation->code_hash);
        $this->assertTrue($invitation->expires_at->isFuture());
        $this->assertMatchesRegularExpression('/^KEJA-[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{4}-[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{4}$/', $invitation->code);
        $this->assertDatabaseHas('audit_logs', ['action' => 'caretaker_invitation.created']);
    }

    public function test_generated_codes_are_unique_and_case_and_spacing_normalized(): void
    {
        $landlord = $this->landlord();
        $first = $this->makeInvitation($landlord);
        $second = $this->makeInvitation($landlord);

        $this->assertNotSame($first->code, $second->code);
        $formatted = strtolower(str_replace('-', ' ', $first->code));
        $this->assertTrue($this->service()->validate('  '.$formatted.'  ')['ok']);
    }

    public function test_landlord_cannot_invite_for_another_landlords_property(): void
    {
        $owner = $this->landlord();
        $stranger = $this->landlord();
        $property = $this->property($owner);

        $this->actingAs($stranger)
            ->post(route('landlord.caretaker-invitations.store'), ['property_id' => $property->id])
            ->assertSessionHasErrors('property_id');

        $this->assertDatabaseCount('caretaker_invitations', 0);
    }

    public function test_validate_accepts_a_valid_code(): void
    {
        $invitation = $this->makeInvitation($this->landlord());

        $result = $this->service()->validate($invitation->code);

        $this->assertTrue($result['ok']);
        $this->assertSame($invitation->id, $result['invitation']->id);
    }

    public function test_existing_opaque_invitation_codes_remain_valid_during_their_expiry_period(): void
    {
        $invitation = $this->makeInvitation($this->landlord());
        $legacyCode = 'aBcDeFgHiJkLmNoPqRsTuVwXyZ1234567890';
        $invitation->update([
            'code' => $legacyCode,
            'code_hash' => hash('sha256', $legacyCode),
        ]);

        $this->assertTrue($this->service()->validate($legacyCode)['ok']);
    }

    public function test_expired_revoked_and_redeemed_codes_are_rejected(): void
    {
        $landlord = $this->landlord();

        $expired = $this->makeInvitation($landlord, null, ['expires_at' => now()->subDay()]);
        $revoked = $this->makeInvitation($landlord, null, ['status' => CaretakerInvitation::STATUS_REVOKED]);
        $redeemed = $this->makeInvitation($landlord, null, ['status' => CaretakerInvitation::STATUS_REDEEMED]);

        $this->assertFalse($this->service()->validate($expired->code)['ok']);
        $this->assertFalse($this->service()->validate($revoked->code)['ok']);
        $this->assertFalse($this->service()->validate($redeemed->code)['ok']);
    }

    public function test_email_restricted_code_rejects_a_mismatched_email(): void
    {
        $invitation = $this->makeInvitation($this->landlord(), null, ['email' => 'right@example.com']);

        $this->assertFalse($this->service()->validate($invitation->code, 'wrong@example.com')['ok']);
        $this->assertTrue($this->service()->validate($invitation->code, 'right@example.com')['ok']);
    }

    public function test_caretaker_cannot_register_without_an_invitation(): void
    {
        $this->post('/register', [
            'name' => 'Sneaky Caretaker',
            'email' => 'sneaky@example.com',
            'role' => 'caretaker',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('invitation_code');

        $this->assertDatabaseMissing('users', ['email' => 'sneaky@example.com']);
        $this->assertGuest();
    }

    public function test_caretaker_registration_with_valid_code_redeems_and_assigns(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $invitation = $this->makeInvitation($landlord, $property);

        $this->post('/register', [
            'name' => 'New Caretaker',
            'email' => 'caretaker@example.com',
            'role' => 'caretaker',
            'invitation_code' => $invitation->code,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();

        $caretaker = User::where('email', 'caretaker@example.com')->firstOrFail();
        $this->assertSame('caretaker', $caretaker->role);
        $this->assertSame(User::STATUS_APPROVED, $caretaker->status);
        $this->assertTrue($caretaker->caretakerProperties()->whereKey($property->id)->exists());

        $fresh = $invitation->fresh();
        $this->assertSame(CaretakerInvitation::STATUS_REDEEMED, $fresh->status);
        $this->assertSame($caretaker->id, $fresh->redeemed_by);
        $this->assertNotNull($fresh->redeemed_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'caretaker_invitation.redeemed']);
    }

    public function test_invitation_code_cannot_be_submitted_for_another_registration_role(): void
    {
        $invitation = $this->makeInvitation($this->landlord());

        $this->post('/register', [
            'name' => 'Not a caretaker',
            'email' => 'not-caretaker@example.com',
            'role' => 'landlord',
            'invitation_code' => $invitation->code,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('invitation_code');

        $this->assertDatabaseMissing('users', ['email' => 'not-caretaker@example.com']);
        $this->assertSame(CaretakerInvitation::STATUS_PENDING, $invitation->fresh()->status);
    }

    public function test_redeemed_code_cannot_be_used_again(): void
    {
        $landlord = $this->landlord();
        $invitation = $this->makeInvitation($landlord);
        $existing = User::factory()->caretaker()->create();

        $this->service()->redeem($invitation, $existing);

        $this->post('/register', [
            'name' => 'Second Caretaker',
            'email' => 'second@example.com',
            'role' => 'caretaker',
            'invitation_code' => $invitation->code,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('invitation_code');

        $this->assertDatabaseMissing('users', ['email' => 'second@example.com']);
    }

    public function test_verify_endpoint_reports_valid_and_invalid_codes(): void
    {
        $invitation = $this->makeInvitation($this->landlord());

        $this->postJson(route('register.verify-invitation-code'), ['invitation_code' => $invitation->code])
            ->assertOk()
            ->assertJson(['valid' => true]);

        $this->postJson(route('register.verify-invitation-code'), ['invitation_code' => 'not-a-real-code'])
            ->assertStatus(422)
            ->assertJson(['valid' => false]);
    }

    public function test_landlord_can_revoke_a_pending_invitation(): void
    {
        $landlord = $this->landlord();
        $invitation = $this->makeInvitation($landlord);

        $this->actingAs($landlord)
            ->post(route('landlord.caretaker-invitations.revoke', $invitation))
            ->assertRedirect();

        $this->assertSame(CaretakerInvitation::STATUS_REVOKED, $invitation->fresh()->status);
        $this->assertFalse($this->service()->validate($invitation->code)['ok']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'caretaker_invitation.revoked']);
    }

    public function test_landlord_cannot_revoke_another_landlords_invitation(): void
    {
        $owner = $this->landlord();
        $stranger = $this->landlord();
        $invitation = $this->makeInvitation($owner);

        $this->actingAs($stranger)
            ->post(route('landlord.caretaker-invitations.revoke', $invitation))
            ->assertForbidden();

        $this->assertSame(CaretakerInvitation::STATUS_PENDING, $invitation->fresh()->status);
    }

    public function test_revoking_caretaker_access_detaches_property_but_preserves_records(): void
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $caretaker = User::factory()->caretaker()->create();
        $property->caretakers()->attach($caretaker->id);

        $this->actingAs($landlord)
            ->delete(route('landlord.caretakers.revoke-access', [$caretaker, $property]))
            ->assertRedirect();

        $this->assertFalse($caretaker->caretakerProperties()->whereKey($property->id)->exists());
        $this->assertDatabaseHas('users', ['id' => $caretaker->id]);
    }
}
