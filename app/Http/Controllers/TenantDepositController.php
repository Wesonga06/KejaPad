<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\DepositService;

class TenantDepositController extends Controller
{
    public function show(DepositService $service)
    {
        $tenant = Tenant::where('user_id', auth()->id())
            ->with(['property', 'unit'])
            ->firstOrFail();

        $deposit = $tenant->securityDeposit ?? $service->ensureForTenant($tenant);

        $this->authorize('view', $deposit);

        $deposit->load(['transactions' => fn ($q) => $q->latest()]);

        $moveOut = $tenant->activeMoveOutRequest()->first();

        return view('tenant.deposit.show', compact('tenant', 'deposit', 'moveOut'));
    }
}
