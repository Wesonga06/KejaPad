<?php

namespace App\Http\Controllers;

use App\Http\Requests\Settlement\RecordTenantResponseRequest;
use App\Models\DepositSettlement;
use App\Services\DepositSettlementService;
use App\Services\MoveOutResolutionService;

class TenantSettlementController extends Controller
{
    public function show(DepositSettlement $settlement, MoveOutResolutionService $resolution)
    {
        $this->authorize('view', $settlement);

        $settlement->load([
            'moveOutRequest.property',
            'moveOutRequest.unit',
            'moveOutRequest.inspections.items',
            'securityDeposit.transactions',
            'events.user',
            'decidedBy',
        ]);

        $comparison = $settlement->moveOutRequest
            ? $resolution->compare($settlement->moveOutRequest)
            : null;

        $deposit = $settlement->securityDeposit;

        return view('tenant.settlement.show', compact('settlement', 'comparison', 'deposit'));
    }

    public function respond(RecordTenantResponseRequest $request, DepositSettlement $settlement, DepositSettlementService $service)
    {
        $service->recordTenantResponse($settlement, auth()->user(), [
            'tenant_comment' => $request->input('tenant_comment'),
            'acknowledge' => $request->boolean('acknowledge'),
            'dispute' => $request->input('dispute', []),
        ]);

        return back()->with('success', 'Your response has been recorded.');
    }
}
