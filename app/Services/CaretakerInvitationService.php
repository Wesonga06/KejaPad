<?php

namespace App\Services;

use App\Models\CaretakerInvitation;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CaretakerInvitationService
{
    // Issue a new invitation for a caretaker to manage a specific property.
    public function create(User $landlord, array $data): CaretakerInvitation
    {
        $code = CaretakerInvitation::generateCode();
        $expiryDays = (int) config('tenancy.invitation_expiry_days', 7);

        return CaretakerInvitation::create([
            'landlord_id' => $landlord->id,
            'property_id' => $data['property_id'],
            'email' => $data['email'] ?? null,
            'name' => $data['name'] ?? null,
            'code' => $code,
            'code_hash' => CaretakerInvitation::hashCode($code),
            'status' => CaretakerInvitation::STATUS_PENDING,
            'expires_at' => Carbon::now()->addDays($expiryDays),
            'created_by' => $landlord->id,
        ]);
    }

    // Validate a code without consuming it. Safe to call from the registration
    // form's "Verify Code" button.
    //
    // @return array{ok: bool, invitation: ?CaretakerInvitation, error: ?string}
    public function validate(string $code, ?string $email = null): array
    {
        $code = trim($code);

        if ($code === '') {
            return $this->invalid('Please enter an invitation code.');
        }

        $invitation = CaretakerInvitation::with(['property', 'landlord'])
            ->where('code_hash', CaretakerInvitation::hashCode($code))
            ->first();

        if (! $invitation) {
            return $this->invalid('This invitation code is not valid.');
        }

        if ($invitation->isRevoked()) {
            return $this->invalid('This invitation has been revoked.');
        }

        if ($invitation->isRedeemed()) {
            return $this->invalid('This invitation has already been used.');
        }

        if ($invitation->isExpired()) {
            return $this->invalid('This invitation has expired.');
        }

        if (! $invitation->landlord?->canAccessLandlordFeatures()) {
            return $this->invalid('The inviting landlord is not active.');
        }

        if (! $invitation->property) {
            return $this->invalid('The property linked to this invitation is no longer available.');
        }

        if ($email !== null && ! $invitation->matchesEmail($email)) {
            return $this->invalid('This invitation was issued for a different email address.');
        }

        return ['ok' => true, 'invitation' => $invitation, 'error' => null];
    }

    // Consume an invitation and attach the caretaker to the property. Must be
    // called inside a transaction; row locking prevents double redemption.
    public function redeem(CaretakerInvitation $invitation, User $caretaker): CaretakerInvitation
    {
        return DB::transaction(function () use ($invitation, $caretaker) {
            $locked = CaretakerInvitation::whereKey($invitation->id)->lockForUpdate()->first();

            if (! $locked || ! $locked->isRedeemable()) {
                throw ValidationException::withMessages([
                    'invitation_code' => 'This invitation is no longer valid.',
                ]);
            }

            if (config('tenancy.invitation_single_use', true) && $locked->isRedeemed()) {
                throw ValidationException::withMessages([
                    'invitation_code' => 'This invitation has already been used.',
                ]);
            }

            if (! $caretaker->isCaretaker()) {
                throw ValidationException::withMessages([
                    'invitation_code' => 'Only caretaker accounts can redeem caretaker invitations.',
                ]);
            }

            $property = $locked->property;
            if (! $property) {
                throw ValidationException::withMessages([
                    'invitation_code' => 'The property linked to this invitation is no longer available.',
                ]);
            }

            $caretaker->caretakerProperties()->syncWithoutDetaching([$property->id]);

            $locked->update([
                'status' => CaretakerInvitation::STATUS_REDEEMED,
                'redeemed_at' => now(),
                'redeemed_by' => $caretaker->id,
            ]);

            return $locked->fresh();
        });
    }

    public function revoke(CaretakerInvitation $invitation, User $actor): CaretakerInvitation
    {
        if ($invitation->isRedeemed()) {
            throw ValidationException::withMessages([
                'invitation' => 'Redeemed invitations cannot be revoked. Revoke the caretaker\'s property access instead.',
            ]);
        }

        $invitation->update([
            'status' => CaretakerInvitation::STATUS_REVOKED,
            'revoked_at' => now(),
        ]);

        return $invitation->fresh();
    }

    // Detach a caretaker from a property while preserving all historical records.
    public function revokeCaretakerAccess(Property $property, User $caretaker): void
    {
        DB::transaction(function () use ($property, $caretaker) {
            $caretaker->caretakerProperties()->detach($property->id);
        });
    }

    protected function invalid(string $message): array
    {
        return ['ok' => false, 'invitation' => null, 'error' => $message];
    }
}
