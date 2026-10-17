<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CaretakerInvitation;
use App\Models\User;
use App\Services\CaretakerInvitationService;
use App\Services\LandlordApprovalService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function __construct(
        protected CaretakerInvitationService $invitations,
        protected LandlordApprovalService $approvals,
    ) {}

    public function create(Request $request): View
    {
        return view('auth.register', [
            'prefillCode' => $request->query('code'),
            'prefilled' => $request->query('code')
                ? $this->invitations->validate((string) $request->query('code'))
                : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'role' => ['required', 'in:landlord,caretaker,tenant'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $role = $request->input('role');
        $invitation = null;

        if ($role === 'caretaker') {
            $result = $this->invitations->validate(
                (string) $request->input('invitation_code'),
                $request->input('email')
            );

            if (! $result['ok']) {
                throw ValidationException::withMessages([
                    'invitation_code' => $result['error'],
                ]);
            }

            $invitation = $result['invitation'];
        }

        $user = DB::transaction(function () use ($request, $role, $invitation) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'role' => $role,
                'password' => Hash::make($request->password),
                'is_active' => true,
                // Landlords await Super Admin approval; everyone else is active.
                'status' => $role === 'landlord'
                    ? User::STATUS_PENDING
                    : User::STATUS_APPROVED,
            ]);

            if ($role === 'caretaker' && $invitation) {
                $this->invitations->redeem($invitation, $user);
            }

            if ($role === 'landlord') {
                $this->approvals->recordSubmission($user);
            }

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('dashboard');
    }

    // Validate an invitation code during caretaker registration without consuming it.
    public function verifyInvitationCode(Request $request): JsonResponse
    {
        $request->validate([
            'invitation_code' => ['required', 'string'],
            'email' => ['nullable', 'email'],
        ]);

        $result = $this->invitations->validate(
            (string) $request->input('invitation_code'),
            $request->input('email')
        );

        if (! $result['ok']) {
            return response()->json(['valid' => false, 'error' => $result['error']], 422);
        }

        /** @var CaretakerInvitation $invitation */
        $invitation = $result['invitation'];

        return response()->json([
            'valid' => true,
            'property' => $invitation->property?->name,
            'landlord' => $invitation->landlord?->name,
            'name' => $invitation->name,
            'email' => $invitation->email,
            'email_restricted' => $invitation->isEmailRestricted(),
        ]);
    }
}
