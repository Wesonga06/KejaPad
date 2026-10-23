<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentReversal;
use App\Models\PaymentReversalAllocation;
use App\Services\PaymentReversalService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentReversalController extends Controller
{
    public function create(Payment $payment)
    {
        $this->authorize('create', [PaymentReversal::class, $payment]);
        $payment->load('allocations.rentRecord');
        $reserved = PaymentReversal::where('payment_id', $payment->id)->whereIn('status', ['pending', 'approved'])->sum('amount');
        $lines = $payment->allocations->map(function ($allocation) {
            $reserved = PaymentReversalAllocation::where('payment_allocation_id', $allocation->id)->whereHas('reversal', fn ($q) => $q->whereIn('status', ['pending', 'approved']))->sum('amount');
            $allocation->reversible = max(0, (float) $allocation->amount - (float) $reserved);

            return $allocation;
        });
        $creditReserved = PaymentReversal::where('payment_id', $payment->id)->where('status', 'pending')->sum('reversed_credit');
        $availableCredit = max(0, (float) $payment->unallocated_amount - (float) $creditReserved);

        return view('landlord.payments.reversals.create', compact('payment', 'lines', 'reserved', 'availableCredit'));
    }

    public function store(Request $request, Payment $payment, PaymentReversalService $service)
    {
        $this->authorize('create', [PaymentReversal::class, $payment]);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'reversed_credit' => ['required', 'numeric', 'gte:0', 'decimal:0,2'],
            'reason' => ['required', 'string', 'max:5000'],
            'allocations' => ['nullable', 'array'],
            'allocations.*' => ['numeric', 'gt:0', 'decimal:0,2'],
        ]);
        $reversal = $service->initiate($payment, $request->user(), $data['amount'], $data['reversed_credit'], $data['reason'], $data['allocations'] ?? []);

        return redirect()->route('landlord.tenants.show', $payment->tenant_id)->with('success', 'Reversal request submitted for independent Super Admin approval.');
    }

    public function index()
    {
        $this->authorize('viewAny', PaymentReversal::class);
        $reversals = PaymentReversal::with(['payment.tenant.property', 'initiator', 'allocations.paymentAllocation.rentRecord'])->latest()->paginate(25);

        return view('super_admin.payments.reversals', compact('reversals'));
    }

    public function approve(Request $request, PaymentReversal $reversal, PaymentReversalService $service)
    {
        $this->authorize('approve', $reversal);
        $service->decide($reversal, $request->user(), true);

        return back()->with('success', 'Accounting reversal approved. Any external refund must be handled and recorded separately.');
    }

    public function reject(Request $request, PaymentReversal $reversal, PaymentReversalService $service)
    {
        $this->authorize('reject', $reversal);
        $data = $request->validate(['reason' => ['required', 'string', 'max:5000']]);
        $service->decide($reversal, $request->user(), false, $data['reason']);

        return back()->with('success', 'Reversal request rejected.');
    }

    public function externalRefund(Request $request, PaymentReversal $reversal, PaymentReversalService $service)
    {
        $this->authorize('recordExternalRefund', $reversal);
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'confirmed', 'failed'])],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);
        $service->recordExternalRefund($reversal, $request->user(), $data['status'], $data['reference'] ?? null);

        return back()->with('success', 'External refund status recorded for audit; no refund was initiated.');
    }
}
