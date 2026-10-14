<?php

namespace App\Http\Controllers;

use App\Http\Requests\MoveOut\StoreMoveOutRequestRequest;
use App\Models\MoveOutRequest;
use App\Models\Tenant;
use App\Services\MoveOutService;

class TenantMoveOutController extends Controller
{
    protected function tenant(): Tenant
    {
        return Tenant::where('user_id', auth()->id())
            ->with(['property', 'unit'])
            ->firstOrFail();
    }

    public function index(MoveOutService $service)
    {
        $tenant = $this->tenant();
        $requests = $tenant->moveOutRequests()->latest()->get();
        $active = $tenant->activeMoveOutRequest()->first();
        $deposit = $tenant->securityDeposit;
        $months = $service->noticePeriodMonths();
        $earliest = $service->earliestPermittedDate(now(), $months);

        return view('tenant.move-out.index', compact('tenant', 'requests', 'active', 'deposit', 'months', 'earliest'));
    }

    public function create(MoveOutService $service)
    {
        $this->authorize('create', MoveOutRequest::class);

        $tenant = $this->tenant();

        if ($tenant->activeMoveOutRequest()->exists()) {
            return redirect()->route('tenant.move-out.index')
                ->with('error', 'You already have an open Notice to Vacate.');
        }

        $months = $service->noticePeriodMonths();
        $earliest = $service->earliestPermittedDate(now(), $months);
        $deposit = $tenant->securityDeposit;

        return view('tenant.move-out.create', compact('tenant', 'earliest', 'months', 'deposit'));
    }

    public function store(StoreMoveOutRequestRequest $request, MoveOutService $service)
    {
        $tenant = $this->tenant();

        $moveOut = $service->createRequest($tenant, $request->validated(), auth()->user());

        return redirect()->route('tenant.move-out.show', $moveOut)
            ->with('success', 'Your Notice to Vacate has been submitted.');
    }

    public function show(MoveOutRequest $moveOut)
    {
        $this->authorize('view', $moveOut);

        $moveOut->load(['property', 'unit', 'reviewedBy', 'inspection.items']);

        $deposit = $moveOut->tenant?->securityDeposit;

        return view('tenant.move-out.show', compact('moveOut', 'deposit'));
    }

    public function cancel(MoveOutRequest $moveOut, MoveOutService $service)
    {
        $this->authorize('cancel', $moveOut);

        $service->transition($moveOut, MoveOutRequest::STATUS_CANCELLED, auth()->user());

        return back()->with('success', 'Your Notice to Vacate has been cancelled.');
    }
}
