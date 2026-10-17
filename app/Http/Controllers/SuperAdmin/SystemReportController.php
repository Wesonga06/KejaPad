<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\SystemReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SystemReportController extends Controller
{
    protected const REPORTS = [
        'users', 'applications', 'properties', 'rent',
        'deposits', 'move-outs', 'sms', 'audit',
    ];

    public function index(Request $request, SystemReportService $reports)
    {
        $this->authorize('view-system-reports');

        [$from, $to] = $this->range($request);

        $summary = $reports->summary($from, $to);

        return view('super_admin.reports.index', [
            'summary' => $summary,
            'from' => $from,
            'to' => $to,
            'reports' => self::REPORTS,
        ]);
    }

    public function export(Request $request, SystemReportService $reports): StreamedResponse
    {
        $this->authorize('view-system-reports');

        [$from, $to] = $this->range($request);

        $report = (string) $request->query('report', 'users');

        if (! in_array($report, self::REPORTS, true)) {
            abort(404);
        }

        $data = $reports->export($report, $from, $to);

        return response()->streamDownload(function () use ($data) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $data['headings']);

            foreach ($data['rows'] as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 'kejapad-'.$report.'-'.now()->format('Ymd_His').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    protected function range(Request $request): array
    {
        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        return [
            filled($validated['from'] ?? null) ? Carbon::parse($validated['from']) : null,
            filled($validated['to'] ?? null) ? Carbon::parse($validated['to']) : null,
        ];
    }
}
