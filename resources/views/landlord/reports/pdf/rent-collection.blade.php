<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Rent Collection Report</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #333; }
        h2 { color: #4c1d95; margin-bottom: 4px; }
        p { margin: 2px 0; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background: #4c1d95; color: white; padding: 7px 6px; text-align: left; }
        td { padding: 6px; border-bottom: 1px solid #eee; }
        tr:nth-child(even) { background: #f9f9f9; }
        .footer { margin-top: 20px; font-size: 10px; color: #999; text-align: right; }
        .summary { margin-top: 10px; display: flex; gap: 20px; }
        .badge { padding: 2px 6px; border-radius: 10px; font-size: 10px; }
        .paid { background: #d1fae5; color: #065f46; }
        .pending { background: #e5e7eb; color: #374151; }
        .partial { background: #fef3c7; color: #92400e; }
        .overdue { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <h2>KejaPad — Rent Collection Report</h2>
    <p>Generated on: {{ now()->format('d M Y H:i') }}</p>

    <table>
        <tr>
            <td><strong>Total Expected:</strong> KES {{ number_format($totalExpected, 2) }}</td>
            <td><strong>Total Collected:</strong> KES {{ number_format($totalCollected, 2) }}</td>
            <td><strong>Outstanding:</strong> KES {{ number_format($totalExpected - $totalCollected, 2) }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Tenant</th>
                <th>Property</th>
                <th>Unit</th>
                <th>Period</th>
                <th>Due</th>
                <th>Paid</th>
                <th>Balance</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($records as $i => $r)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $r->tenant->full_name }}</td>
                <td>{{ $r->property->name }}</td>
                <td>{{ $r->unit->unit_number }}</td>
                <td>{{ $r->monthName() }} {{ $r->year }}</td>
                <td>KES {{ number_format($r->amount_due, 2) }}</td>
                <td>KES {{ number_format($r->amount_paid, 2) }}</td>
                <td>KES {{ number_format($r->balance(), 2) }}</td>
                <td>
                    <span class="badge {{ $r->status }}">
                        {{ ucfirst($r->status) }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">KejaPad Property Management System</div>
</body>
</html>