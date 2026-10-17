<?php

namespace App\Services;

use App\Models\LandlordApprovalAction;
use App\Models\User;
use App\Notifications\LandlordApprovedNotification;
use App\Notifications\LandlordRejectedNotification;
use App\Notifications\LandlordSuspendedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LandlordApprovalService
{
    public function __construct(protected SmsService $sms) {}

    // Record the initial submission when a landlord registers.
    public function recordSubmission(User $landlord): LandlordApprovalAction
    {
        return LandlordApprovalAction::create([
            'user_id' => $landlord->id,
            'action' => LandlordApprovalAction::ACTION_SUBMITTED,
            'performed_by' => $landlord->id,
        ]);
    }

    public function approve(User $landlord, User $admin): User
    {
        $this->assertLandlord($landlord);

        return DB::transaction(function () use ($landlord, $admin) {
            $landlord->update([
                'status' => User::STATUS_APPROVED,
                'approval_reason' => null,
                'approved_at' => now(),
                'rejected_at' => null,
                'approved_by' => $admin->id,
                'is_active' => true,
            ]);

            $this->log($landlord, LandlordApprovalAction::ACTION_APPROVED, null, $admin);

            $landlord->notify(new LandlordApprovedNotification($landlord));

            $this->sms->sendTemplate('landlord_approved', (string) $landlord->phone, [
                'name' => $landlord->name,
            ], ['related' => $landlord]);

            return $landlord->fresh();
        });
    }

    public function reject(User $landlord, string $reason, User $admin): User
    {
        $this->assertLandlord($landlord);

        return DB::transaction(function () use ($landlord, $reason, $admin) {
            $landlord->update([
                'status' => User::STATUS_REJECTED,
                'approval_reason' => $reason,
                'rejected_at' => now(),
                'approved_at' => null,
                'approved_by' => $admin->id,
            ]);

            $this->log($landlord, LandlordApprovalAction::ACTION_REJECTED, $reason, $admin);

            $landlord->notify(new LandlordRejectedNotification($landlord, $reason));

            $this->sms->sendTemplate('landlord_rejected', (string) $landlord->phone, [
                'name' => $landlord->name,
                'reason' => $reason,
            ], ['related' => $landlord]);

            return $landlord->fresh();
        });
    }

    public function suspend(User $landlord, string $reason, User $admin): User
    {
        $this->assertLandlord($landlord);

        return DB::transaction(function () use ($landlord, $reason, $admin) {
            $landlord->update([
                'status' => User::STATUS_SUSPENDED,
                'approval_reason' => $reason,
                'approved_by' => $admin->id,
            ]);

            $this->log($landlord, LandlordApprovalAction::ACTION_SUSPENDED, $reason, $admin);

            $landlord->notify(new LandlordSuspendedNotification($landlord, $reason));

            return $landlord->fresh();
        });
    }

    public function reinstate(User $landlord, User $admin): User
    {
        $this->assertLandlord($landlord);

        return DB::transaction(function () use ($landlord, $admin) {
            $landlord->update([
                'status' => User::STATUS_APPROVED,
                'approval_reason' => null,
                'approved_at' => now(),
                'rejected_at' => null,
                'approved_by' => $admin->id,
                'is_active' => true,
            ]);

            $this->log($landlord, LandlordApprovalAction::ACTION_REINSTATED, null, $admin);

            $landlord->notify(new LandlordApprovedNotification($landlord));

            $this->sms->sendTemplate('landlord_approved', (string) $landlord->phone, [
                'name' => $landlord->name,
            ], ['related' => $landlord]);

            return $landlord->fresh();
        });
    }

    protected function assertLandlord(User $landlord): void
    {
        if (! $landlord->isLandlord()) {
            throw ValidationException::withMessages([
                'application' => 'The selected account is not a landlord application.',
            ]);
        }
    }

    protected function log(User $landlord, string $action, ?string $reason, User $admin): LandlordApprovalAction
    {
        return LandlordApprovalAction::create([
            'user_id' => $landlord->id,
            'action' => $action,
            'reason' => $reason,
            'performed_by' => $admin->id,
        ]);
    }
}
