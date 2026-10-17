<?php

namespace App\Http\Controllers;

use App\Http\Requests\Deposit\RecordDeductionRequest;
use App\Http\Requests\Deposit\RecordDepositReceiptRequest;
use App\Http\Requests\Deposit\RecordRefundRequest;
use App\Models\DepositTransaction;
use App\Models\Property;
use App\Models\SecurityDeposit;
use App\Services\DepositService;
use Illuminate\Http\Request;

class LandlordDepositController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', SecurityDeposit::class);

        $base = SecurityDeposit::whereHas('property', function ($q) {
            $q->where('user_id', auth()->id());
        });

        $query = clone $base;

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($propertyId = $request->integer('property_id')) {
            $query->where('property_id', $propertyId);
        }

        if ($search = $request->string('q')->trim()->toString()) {
            $query->whereHas('tenant', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%");
            });
        }

        $deposits = $query->with(['tenant', 'property', 'unit'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'held' => (float) (clone $base)->sum('received_amount'),
            'outstanding' => (float) (clone $base)
                ->whereIn('status', [SecurityDeposit::STATUS_PENDING, SecurityDeposit::STATUS_PARTIAL])
                ->selectRaw('COALESCE(SUM(required_amount - received_amount), 0) as total')
                ->value('total'),
            'verified' => (clone $base)->where('status', SecurityDeposit::STATUS_VERIFIED)->count(),
            'refunded' => (clone $base)->whereIn('status', [
                SecurityDeposit::STATUS_REFUNDED,
                SecurityDeposit::STATUS_PARTIALLY_REFUNDED,
            ])->count(),
        ];

        $properties = Property::where('user_id', auth()->id())->orderBy('name')->get();

        return view('landlord.deposits.index', compact('deposits', 'properties', 'stats'));
    }

    public function sync(DepositService $service)
    {
        $this->authorize('viewAny', SecurityDeposit::class);

        $created = $service->ensureForLandlord(auth()->user());

        return back()->with('success', $created.' deposit record(s) created for your active tenants.');
    }

    public function show(SecurityDeposit $deposit)
    {
        $this->authorize('view', $deposit);

        $deposit->load([
            'tenant',
            'property',
            'unit',
            'exceptionApprovedBy',
            'transactions.recordedBy',
            'transactions.verifiedBy',
            'transactions.approvedBy',
        ]);

        $moveOut = $deposit->tenant?->activeMoveOutRequest()->first();

        return view('landlord.deposits.show', compact('deposit', 'moveOut'));
    }

    public function recordReceipt(RecordDepositReceiptRequest $request, SecurityDeposit $deposit, DepositService $service)
    {
        $service->recordReceipt($deposit, [
            'amount' => $request->amount,
            'method' => $request->method,
            'reference' => $request->reference,
            'verify' => $request->boolean('verify'),
        ], auth()->user());

        return back()->with('success', 'Deposit receipt recorded.');
    }

    public function verifyReceipt(SecurityDeposit $deposit, DepositTransaction $transaction, DepositService $service)
    {
        $this->authorize('manage', $deposit);
        $this->ensureTransactionBelongsToDeposit($deposit, $transaction);

        $service->verifyReceipt($transaction, auth()->user());

        return back()->with('success', 'Deposit receipt verified.');
    }

    public function verify(SecurityDeposit $deposit, DepositService $service)
    {
        $this->authorize('manage', $deposit);

        $service->verifyDeposit($deposit, auth()->user());

        return back()->with('success', 'Deposit verified. The tenant may now be moved in.');
    }

    public function proposeDeduction(RecordDeductionRequest $request, SecurityDeposit $deposit, DepositService $service)
    {
        $service->proposeDeduction($deposit, [
            'amount' => $request->amount,
            'reason' => $request->reason,
            'evidence' => $request->evidence,
        ], auth()->user());

        return back()->with('success', 'Deduction proposed. It must be approved before it is applied.');
    }

    public function approveDeduction(SecurityDeposit $deposit, DepositTransaction $transaction, DepositService $service)
    {
        $this->authorize('manage', $deposit);
        $this->ensureTransactionBelongsToDeposit($deposit, $transaction);

        $service->approveDeduction($transaction, auth()->user());

        return back()->with('success', 'Deduction approved.');
    }

    public function refund(RecordRefundRequest $request, SecurityDeposit $deposit, DepositService $service)
    {
        $service->recordRefund($deposit, [
            'amount' => $request->amount,
            'method' => $request->method,
            'reference' => $request->reference,
        ], auth()->user());

        return back()->with('success', 'Deposit refund recorded.');
    }

    public function exception(Request $request, SecurityDeposit $deposit, DepositService $service)
    {
        $this->authorize('manage', $deposit);

        $validated = $request->validate([
            'exception_reason' => 'required|string|min:3|max:1000',
        ]);

        $service->recordException($deposit, $validated['exception_reason'], $request->user());

        return back()->with('success', 'Authorized exception recorded. The tenancy may now be moved in.');
    }

    protected function ensureTransactionBelongsToDeposit(SecurityDeposit $deposit, DepositTransaction $transaction): void
    {
        abort_if($transaction->security_deposit_id !== $deposit->id, 404);
    }
}
