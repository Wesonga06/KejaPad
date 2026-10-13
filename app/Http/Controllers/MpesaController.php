<?php

namespace App\Http\Controllers;

use App\Http\Requests\Mpesa\InitiatePaymentRequest;
use App\Models\MpesaPayment;
use App\Models\Property;
use App\Models\RentRecord;
use App\Models\Tenant;
use App\Services\MpesaService;
use App\Services\RentAllocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MpesaController extends Controller
{
    protected $mpesa;

    protected RentAllocationService $rentAllocations;

    public function __construct(MpesaService $mpesa, RentAllocationService $rentAllocations)
    {
        $this->mpesa = $mpesa;
        $this->rentAllocations = $rentAllocations;
    }

    // Show Pay Rent page for tenant
    public function payPage(RentRecord $rent)
    {
        $this->authorize('pay', $rent);
        abort_if($rent->status === 'paid', 403);

        $tenant = Tenant::where('user_id', auth()->id())->firstOrFail();

        // Get any pending payment for this record
        $pendingPayment = MpesaPayment::where('rent_record_id', $rent->id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        return view('tenant.pay', compact('rent', 'tenant', 'pendingPayment'));
    }

    // Initiate STK Push
    public function initiate(InitiatePaymentRequest $request, RentRecord $rent)
    {
        $tenant = Tenant::where('user_id', auth()->id())->firstOrFail();

        $amount = min((float) $request->amount, $rent->balance());

        if ($amount <= 0) {
            return back()->with('error', 'This rent record is already fully paid.');
        }

        // Check no active pending payment already
        $existing = MpesaPayment::where('rent_record_id', $rent->id)
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->exists();

        if ($existing) {
            return redirect()->route('tenant.payment.pending', $rent)
                ->with('error', 'You already have a pending payment. Check your phone.');
        }

        // Initiate STK Push
        $response = $this->mpesa->stkPush(
            $request->phone,
            $amount,
            'RENT-'.$rent->id,
            'Rent Payment '.$rent->monthName().' '.$rent->year
        );

        // Check for error
        if (isset($response['error']) && $response['error']) {
            return back()->with('error', $response['errorMessage']);
        }

        // Safaricom success check
        if (isset($response['ResponseCode']) && $response['ResponseCode'] === '0') {
            MpesaPayment::create([
                'tenant_id' => $tenant->id,
                'rent_record_id' => $rent->id,
                'phone_number' => $this->mpesa->formatPhone($request->phone),
                'amount' => $amount,
                'checkout_request_id' => $response['CheckoutRequestID'],
                'merchant_request_id' => $response['MerchantRequestID'],
                'status' => 'pending',
            ]);

            return redirect()->route('tenant.payment.pending', $rent)
                ->with('success', 'STK Push sent! Check your phone and enter your M-Pesa PIN.');
        }

        // Safaricom returned an error
        $errorMsg = $response['errorMessage']
                    ?? $response['ResultDesc']
                    ?? $response['CustomerMessage']
                    ?? 'M-Pesa request failed. Please try again.';

        return back()->with('error', $errorMsg);
    }

    // Pending payment page
    public function pending(RentRecord $rent)
    {
        $this->authorize('pay', $rent);

        $payment = MpesaPayment::where('rent_record_id', $rent->id)
            ->latest()
            ->firstOrFail();

        return view('tenant.pending-payment', compact('rent', 'payment'));
    }

    // M-Pesa Callback (called by Safaricom)
    public function callback(Request $request)
    {
        if (! $this->isAllowedCallbackSource($request)) {
            Log::warning('M-Pesa callback from unauthorized IP', ['ip' => $request->ip()]);

            return response()->json(['status' => 'forbidden'], 403);
        }

        $body = $request->input('Body.stkCallback');
        $checkoutRequestId = $body['CheckoutRequestID'] ?? null;
        $resultCode = $body['ResultCode'] ?? null;
        $resultDesc = $body['ResultDesc'] ?? null;

        Log::info('M-Pesa Callback', [
            'checkout_request_id' => $checkoutRequestId,
            'result_code' => $resultCode,
        ]);

        if (! $checkoutRequestId) {
            return response()->json(['status' => 'error']);
        }

        $payment = MpesaPayment::where('checkout_request_id', $checkoutRequestId)->first();

        if (! $payment) {
            return response()->json(['status' => 'not found']);
        }

        DB::transaction(function () use ($payment, $body, $resultCode, $resultDesc, $checkoutRequestId) {
            $payment = MpesaPayment::whereKey($payment->id)->lockForUpdate()->first();

            if (in_array($payment->status, ['confirmed', 'failed'], true)) {
                return;
            }

            if ((int) $resultCode === 0) {
                [$receiptNumber, $paidAmount] = $this->extractMetadata($body);

                if ($paidAmount !== null && (float) $paidAmount + 0.01 < (float) $payment->amount) {
                    Log::warning('M-Pesa callback amount mismatch', [
                        'checkout_request_id' => $checkoutRequestId,
                        'expected' => $payment->amount,
                        'received' => $paidAmount,
                    ]);
                }

                $payment->update([
                    'status' => 'confirmed',
                    'mpesa_receipt_number' => $receiptNumber,
                    'result_code' => $resultCode,
                    'result_desc' => $resultDesc,
                ]);

                $this->rentAllocations->recordPayment(
                    $payment->rentRecord->tenant,
                    (float) $payment->amount,
                    [
                        'payment_date' => now()->toDateString(),
                        'reference' => $receiptNumber,
                        'method' => 'mpesa',
                        'source' => 'tenant',
                        'mpesa_payment_id' => $payment->id,
                    ],
                );
            } else {
                $payment->update([
                    'status' => 'failed',
                    'result_code' => $resultCode,
                    'result_desc' => $resultDesc,
                ]);
            }
        });

        return response()->json(['status' => 'success']);
    }

    /**
     * @return array{0: ?string, 1: ?float}
     */
    protected function extractMetadata(array $body): array
    {
        $receiptNumber = null;
        $paidAmount = null;

        foreach ($body['CallbackMetadata']['Item'] ?? [] as $item) {
            match ($item['Name'] ?? null) {
                'MpesaReceiptNumber' => $receiptNumber = $item['Value'] ?? null,
                'Amount' => $paidAmount = isset($item['Value']) ? (float) $item['Value'] : null,
                default => null,
            };
        }

        return [$receiptNumber, $paidAmount];
    }

    protected function isAllowedCallbackSource(Request $request): bool
    {
        if (! config('mpesa.callback_enforce_ip', true)) {
            return true;
        }

        $allowed = config('mpesa.callback_allowed_ips', []);

        if (empty($allowed)) {
            return true;
        }

        return in_array($request->ip(), $allowed, true);
    }

    // Check payment status via AJAX
    public function checkStatus(MpesaPayment $payment)
    {
        $this->authorize('view', $payment);

        return response()->json([
            'status' => $payment->status,
            'mpesa_receipt_number' => $payment->mpesa_receipt_number,
            'result_desc' => $payment->result_desc,
        ]);
    }

    // Landlord: View all M-Pesa payments
    public function landlordPayments(Request $request)
    {
        $this->authorize('viewAny', MpesaPayment::class);

        $propertyIds = Property::where('user_id', auth()->id())->pluck('id');

        $base = MpesaPayment::whereHas('tenant', function ($q) use ($propertyIds) {
            $q->whereIn('property_id', $propertyIds);
        });

        $pendingCount = (clone $base)->where('status', 'pending')->count();
        $confirmedCount = (clone $base)->where('status', 'confirmed')->count();
        $failedCount = (clone $base)->where('status', 'failed')->count();
        $confirmedTotal = (clone $base)->where('status', 'confirmed')->sum('amount');

        $payments = $base->with(['tenant', 'rentRecord'])
            ->latest()
            ->paginate(20);

        return view('landlord.mpesa-payments', compact(
            'payments',
            'pendingCount',
            'confirmedCount',
            'failedCount',
            'confirmedTotal'
        ));
    }

    // Landlord confirms a payment manually
    public function landlordConfirm(MpesaPayment $payment)
    {
        $this->authorize('confirm', $payment);

        if ($payment->status === 'confirmed') {
            return back()->with('error', 'This payment has already been confirmed.');
        }

        DB::transaction(function () use ($payment) {
            $payment = MpesaPayment::whereKey($payment->id)->lockForUpdate()->first();

            if ($payment->status === 'confirmed') {
                return;
            }

            $payment->update([
                'landlord_confirmed' => true,
                'confirmed_at' => now(),
                'status' => 'confirmed',
            ]);

            $this->rentAllocations->recordPayment(
                $payment->rentRecord->tenant,
                (float) $payment->amount,
                [
                    'payment_date' => now()->toDateString(),
                    'reference' => $payment->mpesa_receipt_number ?? 'MANUAL-'.$payment->id,
                    'method' => 'mpesa',
                    'source' => 'landlord',
                    'mpesa_payment_id' => $payment->id,
                ],
            );
        });

        return back()->with('success', 'Payment confirmed and rent record updated.');
    }

    // Landlord rejects a payment
    public function landlordReject(MpesaPayment $payment)
    {
        $this->authorize('reject', $payment);

        $payment->update([
            'status' => 'failed',
            'result_desc' => 'Rejected by landlord',
        ]);

        return back()->with('success', 'Payment rejected.');
    }
}
