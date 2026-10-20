<?php

namespace App\Http\Controllers;

use App\Http\Requests\CaretakerInvitation\StoreCaretakerInvitationRequest;
use App\Models\CaretakerInvitation;
use App\Models\Property;
use App\Models\User;
use App\Services\CaretakerInvitationService;
use Illuminate\Http\Request;

class LandlordCaretakerInvitationController extends Controller
{
    public function __construct(protected CaretakerInvitationService $invitations) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', CaretakerInvitation::class);

        $landlord = $request->user();

        $invitations = CaretakerInvitation::where('landlord_id', $landlord->id)
            ->with('property')
            ->latest()
            ->paginate(15);

        $properties = Property::where('user_id', $landlord->id)
            ->with(['caretakers' => fn ($query) => $query->orderBy('name')])
            ->orderBy('name')
            ->get();

        $newInvitation = session('new_invitation_id')
            ? CaretakerInvitation::with('property')->find(session('new_invitation_id'))
            : null;

        return view('landlord.caretaker-invitations.index', compact('invitations', 'properties', 'newInvitation'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', CaretakerInvitation::class);

        $properties = Property::where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get();

        return view('landlord.caretaker-invitations.create', compact('properties'));
    }

    public function store(StoreCaretakerInvitationRequest $request)
    {
        $invitation = $this->invitations->create($request->user(), $request->validated());

        return redirect()->route('landlord.caretaker-invitations.index')
            ->with('success', 'Invitation created. Share the code with your caretaker.')
            ->with('new_invitation_id', $invitation->id);
    }

    public function revoke(Request $request, CaretakerInvitation $invitation)
    {
        $this->authorize('revoke', $invitation);

        $this->invitations->revoke($invitation, $request->user());

        return back()->with('success', 'Invitation revoked.');
    }

    public function revokeCaretakerAccess(Request $request, User $caretaker, Property $property)
    {
        $this->authorize('revokeAccess', $property);

        if (! $caretaker->isCaretaker()) {
            abort(404);
        }

        if (! $caretaker->caretakerProperties()->whereKey($property->id)->exists()) {
            return back()->with('error', 'This caretaker is not assigned to that property.');
        }

        $this->invitations->revokeCaretakerAccess($property, $caretaker);

        return back()->with('success', 'Caretaker access to '.$property->name.' has been revoked.');
    }
}
