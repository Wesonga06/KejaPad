<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\DepositSettlement;
use App\Services\MoveOutResolutionService;
use Illuminate\Http\Request;

class SettlementController extends Controller
{
    // Read-only oversight listing of every deposit settlement on the platform.
    public function index(Request $request)
    {
        $this->authorize('viewAny', DepositSettlement::class);

        $query = DepositSettlement::with(['moveOutRequest.tenant', 'moveOutRequest.unit', 'moveOutRequest.property']);

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($search = $request->string('q')->trim()->toString()) {
            $query->whereHas('moveOutRequest.tenant', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%");
            });
        }

        $settlements = $query->latest()->paginate(20)->withQueryString();

        $stats = [
            'total' => DepositSettlement::count(),
            'disputed' => DepositSettlement::where('status', DepositSettlement::STATUS_DISPUTED)->count(),
            'settled' => DepositSettlement::where('status', DepositSettlement::STATUS_SETTLED)->count(),
            'refunded' => (float) DepositSettlement::sum('refunded_amount'),
        ];

        return view('super_admin.settlements.index', compact('settlements', 'stats'));
    }

    public function show(DepositSettlement $settlement, MoveOutResolutionService $resolution)
    {
        $this->authorize('view', $settlement);

        $settlement->load([
            'moveOutRequest.tenant',
            'moveOutRequest.unit',
            'moveOutRequest.property',
            'moveOutRequest.inspections.items',
            'securityDeposit.transactions',
            'events.user',
            'decidedBy',
            'settledBy',
        ]);

        $comparison = $settlement->moveOutRequest
            ? $resolution->compare($settlement->moveOutRequest)
            : null;

        $deposit = $settlement->securityDeposit;

        return view('super_admin.settlements.show', compact('settlement', 'comparison', 'deposit'));
    }
}
