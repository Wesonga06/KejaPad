<?php

namespace App\Http\Controllers;

use App\Http\Requests\Settlement\RecordDecisionRequest;
use App\Http\Requests\Settlement\RecordSettlementRefundRequest;
use App\Http\Requests\Settlement\SendSettlementRequest;
use App\Models\DepositSettlement;
use App\Models\MoveOutRequest;
use App\Services\DepositSettlementService;
use App\Services\MoveOutResolutionService;
use Illuminate\Http\Request;

class LandlordSettlementController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', DepositSettlement::class);

        $base = DepositSettlement::whereHas('moveOutRequest.property', function ($q) {
            $q->where('user_id', auth()->id());
        });

        $query = clone $base;

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($search = $request->string('q')->trim()->toString()) {
            $query->whereHas('moveOutRequest.tenant', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%");
            });
        }

        $settlements = $query->with(['moveOutRequest.tenant', 'moveOutRequest.unit', 'moveOutRequest.property'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'draft' => (clone $base)->where('status', DepositSettlement::STATUS_DRAFT)->count(),
            'awaiting_tenant' => (clone $base)->whereIn('status', [
                DepositSettlement::STATUS_AWAITING_TENANT,
                DepositSettlement::STATUS_TENANT_RESPONDED,
                DepositSettlement::STATUS_DISPUTED,
            ])->count(),
            'approved' => (clone $base)->where('status', DepositSettlement::STATUS_APPROVED)->count(),
            'settled' => (clone $base)->where('status', DepositSettlement::STATUS_SETTLED)->count(),
        ];

        return view('landlord.settlements.index', compact('settlements', 'stats'));
    }

    public function show(DepositSettlement $settlement, MoveOutResolutionService $resolution)
    {
        $this->authorize('view', $settlement);

        $settlement->load([
            'moveOutRequest.tenant',
            'moveOutRequest.unit',
            'moveOutRequest.property',
            'moveOutRequest.inspections.items',
            'moveOutRequest.inspections.caretaker',
            'securityDeposit.transactions.recordedBy',
            'securityDeposit.transactions.approvedBy',
            'securityDeposit.transactions.verifiedBy',
            'events.user',
            'decidedBy',
            'settledBy',
        ]);

        $comparison = $settlement->moveOutRequest
            ? $resolution->compare($settlement->moveOutRequest)
            : null;

        $deposit = $settlement->securityDeposit;

        return view('landlord.settlements.show', compact('settlement', 'comparison', 'deposit'));
    }

    public function store(MoveOutRequest $moveOut, DepositSettlementService $service)
    {
        $this->authorize('view', $moveOut);
        abort_unless($moveOut->property?->user_id === auth()->id(), 403);

        $settlement = $service->ensure($moveOut, auth()->user());

        return redirect()->route('landlord.settlements.show', $settlement)
            ->with('success', 'Deposit settlement statement ready for review.');
    }

    public function send(SendSettlementRequest $request, DepositSettlement $settlement, DepositSettlementService $service)
    {
        $service->sendToTenant($settlement, auth()->user());

        return back()->with('success', 'Statement sent to the tenant for review.');
    }

    public function decide(RecordDecisionRequest $request, DepositSettlement $settlement, DepositSettlementService $service)
    {
        $service->recordDecision($settlement, auth()->user(), $request->validated());

        return back()->with('success', 'Decision recorded. The statement is ready to settle.');
    }

    public function settle(RecordSettlementRefundRequest $request, DepositSettlement $settlement, DepositSettlementService $service)
    {
        $service->settle($settlement, auth()->user(), $request->validated());

        return back()->with('success', 'Deposit settlement completed.');
    }
}
