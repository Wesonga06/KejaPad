<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdatePaymentSettingsRequest;
use App\Models\MpesaPayment;
use App\Models\Payment;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentSettingController extends Controller
{
    public function index()
    {
        $this->authorize('manage-payments');

        $enabledMethods = (array) config('payments.methods_enabled', []);
        $methods = config('payments.methods', []);
        $requireVerification = (bool) config('payments.require_verification', false);

        $status = [
            'env' => config('mpesa.env', 'sandbox'),
            'shortcode' => config('mpesa.shortcode'),
            'callback_url' => config('mpesa.callback_url'),
            'callback_enforce_ip' => (bool) config('mpesa.callback_enforce_ip', true),
            'allowed_ips' => (array) config('mpesa.callback_allowed_ips', []),
            'credentials' => filled(config('mpesa.consumer_key'))
                && filled(config('mpesa.consumer_secret'))
                && filled(config('mpesa.passkey')),
        ];

        $summary = [
            'payments' => Payment::count(),
            'payments_total' => (float) Payment::sum('amount'),
            'by_method' => Payment::query()
                ->selectRaw('method, count(*) as total, COALESCE(SUM(amount), 0) as value')
                ->groupBy('method')->get()->keyBy('method'),
            'mpesa_total' => MpesaPayment::count(),
            'mpesa_pending' => MpesaPayment::where('status', 'pending')->count(),
            'mpesa_confirmed' => MpesaPayment::where('status', 'confirmed')->count(),
            'mpesa_failed' => MpesaPayment::where('status', 'failed')->count(),
        ];

        $recent = MpesaPayment::with('tenant')->latest()->limit(8)->get();

        return view('super_admin.settings.payment', compact(
            'enabledMethods', 'methods', 'requireVerification', 'status', 'summary', 'recent'
        ));
    }

    public function update(UpdatePaymentSettingsRequest $request, SettingsService $settings)
    {
        $ips = collect(explode(',', (string) $request->input('callback_allowed_ips')))
            ->map(fn ($value) => trim($value))
            ->filter()
            ->values()
            ->all();

        $changed = $settings->update([
            'payments.methods_enabled' => $request->input('methods'),
            'payments.require_verification' => $request->boolean('require_verification'),
            'mpesa.callback_enforce_ip' => $request->boolean('callback_enforce_ip'),
            'mpesa.callback_allowed_ips' => $ips,
        ], $request->user(), 'payments');

        return redirect()->route('super_admin.payments.index')
            ->with('success', $changed === []
                ? 'Payment settings saved. No values were changed.'
                : 'Payment settings saved. '.count($changed).' value(s) updated.');
    }

    // Read-only monitoring of tenant/landlord transactions.
    public function transactions(Request $request)
    {
        $this->authorize('manage-payments');

        $filters = [
            'status' => (string) $request->query('status', ''),
            'q' => (string) $request->query('q', ''),
        ];

        $payments = $this->transactionQuery($filters)->paginate(25)->withQueryString();

        $totals = [
            'count' => $this->transactionQuery($filters)->count(),
            'amount' => (float) $this->transactionQuery($filters)->sum('amount'),
        ];

        return view('super_admin.payments.transactions', compact('payments', 'filters', 'totals'));
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('manage-payments');

        $filters = [
            'status' => (string) $request->query('status', ''),
            'q' => (string) $request->query('q', ''),
        ];

        $rows = $this->transactionQuery($filters)->limit(5000)->get();

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Receipt', 'Tenant', 'Phone', 'Amount', 'Status', 'Landlord confirmed', 'Created']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->id,
                    $row->mpesa_receipt_number,
                    $row->tenant?->full_name,
                    $row->phone_number,
                    number_format((float) $row->amount, 2, '.', ''),
                    $row->status,
                    $row->landlord_confirmed ? 'yes' : 'no',
                    $row->created_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, 'kejapad-transactions-'.now()->format('Ymd_His').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    protected function transactionQuery(array $filters)
    {
        return MpesaPayment::query()
            ->with('tenant')
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $like = '%'.$filters['q'].'%';
                $query->where(fn ($q) => $q
                    ->where('mpesa_receipt_number', 'like', $like)
                    ->orWhere('phone_number', 'like', $like));
            })
            ->latest();
    }
}
