<?php

namespace App\Http\Controllers;

use App\Http\Requests\LandlordApproval\RejectLandlordRequest;
use App\Http\Requests\LandlordApproval\SuspendLandlordRequest;
use App\Models\User;
use App\Services\LandlordApprovalService;
use Illuminate\Http\Request;

class LandlordApplicationController extends Controller
{
    public function __construct(protected LandlordApprovalService $approval) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $status = $request->query('status');

        $applications = User::where('role', 'landlord')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $counts = [
            'pending' => User::where('role', 'landlord')->where('status', User::STATUS_PENDING)->count(),
            'approved' => User::where('role', 'landlord')->where('status', User::STATUS_APPROVED)->count(),
            'rejected' => User::where('role', 'landlord')->where('status', User::STATUS_REJECTED)->count(),
            'suspended' => User::where('role', 'landlord')->where('status', User::STATUS_SUSPENDED)->count(),
        ];

        return view('super_admin.landlord-applications.index', compact('applications', 'status', 'counts'));
    }

    public function show(User $landlord)
    {
        $this->authorize('review', $landlord);

        $landlord->load(['landlordApprovalActions.performedBy']);

        return view('super_admin.landlord-applications.show', compact('landlord'));
    }

    public function approve(Request $request, User $landlord)
    {
        $this->authorize('review', $landlord);

        $this->approval->approve($landlord, $request->user());

        return redirect()->route('super_admin.landlord-applications.show', $landlord)
            ->with('success', $landlord->name.' has been approved as a landlord.');
    }

    public function reject(RejectLandlordRequest $request, User $landlord)
    {
        $this->approval->reject($landlord, $request->validated('reason'), $request->user());

        return redirect()->route('super_admin.landlord-applications.show', $landlord)
            ->with('success', $landlord->name.'\'s application has been rejected.');
    }

    public function suspend(SuspendLandlordRequest $request, User $landlord)
    {
        $this->approval->suspend($landlord, $request->validated('reason'), $request->user());

        return redirect()->route('super_admin.landlord-applications.show', $landlord)
            ->with('success', $landlord->name.'\'s account has been suspended.');
    }

    public function reinstate(Request $request, User $landlord)
    {
        $this->authorize('review', $landlord);

        $this->approval->reinstate($landlord, $request->user());

        return redirect()->route('super_admin.landlord-applications.show', $landlord)
            ->with('success', $landlord->name.'\'s account has been reinstated.');
    }
}
