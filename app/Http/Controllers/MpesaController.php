<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MpesaPayment;
use App\Models\RentRecord;
use App\Models\Tenant;
use App\Services\MpesaService;
use Illuminate\Support\Facades\Log;

class MpesaController extends Controller
{
    protected $mpesa;

    public function __construct(MpesaService $mpesa)
    {
        $this->mpesa = $mpesa;
    }

    // Show Pay Rent page for tenant
    public function payPage(RentRecord $rent)
    {
        $tenant = Tenant::where('user_id', auth()->id())->firstOrFail();
        abort_if($rent->tenant_id !== $tenant->id, 403);
        abort_if($rent->status === 'paid', 403);

        // Get any pending payment for this record
        $pendingPayment = MpesaPayment::where('rent_record_id', $rent->id)
                                       ->where('status', 'pending')
                                       ->latest()
                                       ->first();

        return view('tenant.pay', compact('rent', 'tenant', 'pendingPayment'));
    }

    // Initiate STK Push
    public function initiate(Request $request, RentRecord $rent)
    {
        $tenant = Tenant::where('user_id', auth()->id())->firstOrFail();
        abort_if($rent->tenant_id !== $tenant->id, 403);

        $request->validate([
            'phone'  => 'required|string|min:10|max:13',
            'amount' => 'required|numeric|min:1',
        ]);

        $amount = min($request->amount, $rent->balance());

        if ($amount <= 0) {
            return back()->with('error', 'This rent record is already fully paid.');
        }

        // Initiate STK Push
        $response = $this->mpesa->stkPush(
            $request->phone,
            $amount,
            'KEJAPAD-' . $rent->id,
            'Rent for ' . $rent->monthName() . ' ' . $rent->year
        );

        if (isset($response['ResponseCode']) && $response['ResponseCode'] === '0') {
            // Save payment record
            MpesaPayment::create([
                'tenant_id'           => $tenant->id,
                'rent_record_id'      => $rent->id,
                'phone_number'        => $this->mpesa->formatPhone($request->phone),
                'amount'              => $amount,
                'checkout_request_id' => $response['CheckoutRequestID'],
                'merchant_request_id' => $response['MerchantRequestID'],
                'status'              => 'pending',
            ]);

            return redirect()->route('tenant.payment.pending', $rent)
                             ->with('success', 'STK Push sent! Check your phone and enter your M-Pesa PIN.');
        }

        $errorMsg = $response['errorMessage']
                    ?? $response['CustomerMessage']
                    ?? 'Failed to initiate payment. Please try again.';

        return back()->with('error', $errorMsg);
    }

    // Pending payment page
    public function pending(RentRecord $rent)
    {
        $tenant = Tenant::where('user_id', auth()->id())->firstOrFail();
        abort_if($rent->tenant_id !== $tenant->id, 403);

        $payment = MpesaPayment::where('rent_record_id', $rent->id)
                                ->latest()
                                ->firstOrFail();

        return view('tenant.payment-pending', compact('rent', 'payment'));
    }

    // M-Pesa Callback (called by Safaricom)
    public function callback(Request $request)
    {
        Log::info('M-Pesa Callback', $request->all());

        $data = $request->all();

        $body              = $data['Body']['stkCallback'] ?? null;
        $checkoutRequestId = $body['CheckoutRequestID'] ?? null;
        $resultCode        = $body['ResultCode'] ?? null;
        $resultDesc        = $body['ResultDesc'] ?? null;

        if (!$checkoutRequestId) {
            return response()->json(['status' => 'error']);
        }

        $payment = MpesaPayment::where('checkout_request_id', $checkoutRequestId)->first();

        if (!$payment) {
            return response()->json(['status' => 'not found']);
        }

        if ($resultCode === 0) {
            // Payment successful
            $callbackMetadata = $body['CallbackMetadata']['Item'] ?? [];
            $receiptNumber    = null;

            foreach ($callbackMetadata as $item) {
                if ($item['Name'] === 'MpesaReceiptNumber') {
                    $receiptNumber = $item['Value'];
                }
            }

            $payment->update([
                'status'               => 'confirmed',
                'mpesa_receipt_number' => $receiptNumber,
                'result_code'          => $resultCode,
                'result_desc'          => $resultDesc,
            ]);

            // Update rent record
            $rent       = $payment->rentRecord;
            $totalPaid  = $rent->amount_paid + $payment->amount;
            $newStatus  = $totalPaid >= $rent->amount_due ? 'paid' : 'partial';

            $rent->update([
                'amount_paid'      => min($totalPaid, $rent->amount_due),
                'payment_date'     => now()->toDateString(),
                'transaction_code' => $receiptNumber,
                'status'           => $newStatus,
            ]);

        } else {
            // Payment failed or cancelled
            $payment->update([
                'status'      => 'failed',
                'result_code' => $resultCode,
                'result_desc' => $resultDesc,
            ]);
        }

        return response()->json(['status' => 'success']);
    }

    // Check payment status via AJAX
    public function checkStatus(MpesaPayment $payment)
    {
        $tenant = Tenant::where('user_id', auth()->id())->firstOrFail();
        abort_if($payment->tenant_id !== $tenant->id, 403);

        return response()->json([
            'status'               => $payment->status,
            'mpesa_receipt_number' => $payment->mpesa_receipt_number,
            'result_desc'          => $payment->result_desc,
        ]);
    }

    // Landlord: View all M-Pesa payments
    public function landlordPayments(Request $request)
    {
        $propertyIds = \App\Models\Property::where('user_id', auth()->id())->pluck('id');

        $payments = MpesaPayment::whereHas('tenant', function ($q) use ($propertyIds) {
                        $q->whereIn('property_id', $propertyIds);
                    })
                    ->with(['tenant', 'rentRecord'])
                    ->latest()
                    ->get();

        return view('landlord.mpesa-payments', compact('payments'));
    }

    // Landlord confirms a payment manually
    public function landlordConfirm(MpesaPayment $payment)
    {
        $propertyIds = \App\Models\Property::where('user_id', auth()->id())->pluck('id');
        abort_if(!$propertyIds->contains($payment->tenant->property_id), 403);

        $payment->update([
            'landlord_confirmed' => true,
            'confirmed_at'       => now(),
            'status'             => 'confirmed',
        ]);

        // Update rent record
        $rent      = $payment->rentRecord;
        $totalPaid = $rent->amount_paid + $payment->amount;
        $newStatus = $totalPaid >= $rent->amount_due ? 'paid' : 'partial';

        $rent->update([
            'amount_paid'      => min($totalPaid, $rent->amount_due),
            'payment_date'     => now()->toDateString(),
            'transaction_code' => $payment->mpesa_receipt_number ?? 'MANUAL-' . $payment->id,
            'status'           => $newStatus,
        ]);

        return back()->with('success', 'Payment confirmed and rent record updated.');
    }

    // Landlord rejects a payment
    public function landlordReject(MpesaPayment $payment)
    {
        $propertyIds = \App\Models\Property::where('user_id', auth()->id())->pluck('id');
        abort_if(!$propertyIds->contains($payment->tenant->property_id), 403);

        $payment->update([
            'status'      => 'failed',
            'result_desc' => 'Rejected by landlord',
        ]);

        return back()->with('success', 'Payment rejected.');
    }
}