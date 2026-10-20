<?php

namespace App\Services;

use App\Models\CaretakerInvitation;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CaretakerInvitationService
{
    public function __construct(protected AuditService $audit) {}

    // Issue a new invitation for a caretaker to manage a specific property.
    public function create(User $landlord, array $data): CaretakerInvitation
    {
        $expiryDays = (int) config('tenancy.invitation_expiry_days', 7);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $code = CaretakerInvitation::generateCode();
            $codeHash = CaretakerInvitation::hashCode($code);

            if (CaretakerInvitation::where('code_hash', $codeHash)->exists()) {
                continue;
            }

            try {
                return DB::transaction(function () use ($landlord, $data, $code, $codeHash, $expiryDays) {
                    $invitation = CaretakerInvitation::create([
                        'landlord_id' => $landlord->id,
                        'property_id' => $data['property_id'],
                        'email' => $data['email'] ?? null,
                        'name' => $data['name'] ?? null,
                        'code' => $code,
                        'code_hash' => $codeHash,
                        'status' => CaretakerInvitation::STATUS_PENDING,
                        'expires_at' => Carbon::now()->addDays($expiryDays),
                        'created_by' => $landlord->id,
                    ]);

                    $this->audit->record('caretaker_invitation.created', 'Caretaker invitation created.', [], [
                        'property_id' => $invitation->property_id,
                        'expires_at' => $invitation->expires_at?->toIso8601String(),
                    ], $invitation, $landlord);

                    return $invitation;
                });
            } catch (QueryException $exception) {
                if (! $this->isUniqueConstraintViolation($exception)) {
                    throw $exception;
                }
            }
        }

        throw new \RuntimeException('Unable to generate a unique caretaker invitation code.');
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

        $hashes = array_unique([
            CaretakerInvitation::hashCode($code),
            hash('sha256', $code), // Accept existing mixed-case URL tokens during their expiry period.
        ]);

        $invitation = CaretakerInvitation::with(['property', 'landlord'])
            ->whereIn('code_hash', $hashes)
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

            if (! $locked->matchesEmail($caretaker->email) || ! $locked->landlord?->canAccessLandlordFeatures()) {
                throw ValidationException::withMessages([
                    'invitation_code' => 'This invitation is no longer valid for this account.',
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

            $this->audit->record('caretaker_invitation.redeemed', 'Caretaker invitation redeemed.', [], [
                'property_id' => $locked->property_id,
                'caretaker_id' => $caretaker->id,
            ], $locked, $caretaker);

            return $locked->fresh();
        });
    }

    public function revoke(CaretakerInvitation $invitation, User $actor): CaretakerInvitation
    {
        return DB::transaction(function () use ($invitation, $actor) {
            $locked = CaretakerInvitation::whereKey($invitation->id)->lockForUpdate()->first();

            if (! $locked || $locked->isRedeemed()) {
                throw ValidationException::withMessages([
                    'invitation' => 'Redeemed invitations cannot be revoked. Revoke the caretaker\'s property access instead.',
                ]);
            }

            $locked->update([
                'status' => CaretakerInvitation::STATUS_REVOKED,
                'revoked_at' => now(),
            ]);

            $this->audit->record('caretaker_invitation.revoked', 'Caretaker invitation revoked.', [], [
                'property_id' => $locked->property_id,
            ], $locked, $actor);

            return $locked->fresh();
        });
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

    protected function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? '');
        $driverCode = (int) ($exception->errorInfo[1] ?? 0);
        $message = strtolower($exception->getMessage());

        return $sqlState === '23505'
            || $driverCode === 1062
            || str_contains($message, 'unique constraint')
            || str_contains($message, 'duplicate entry');
    }
}
