<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Outstanding Balances</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #333; }
        h2 { color: #dc2626; margin-bottom: 4px; }
        p { margin: 2px 0; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background: #dc2626; color: white; padding: 7px 6px; text-align: left; }
        td { padding: 6px; border-bottom: 1px solid #eee; }
        tr:nth-child(even) { background: #f9f9f9; }
        .footer { margin-top: 20px; font-size: 10px; color: #999; text-align: right; }
    </style>
</head>
<body>
    <h2>KejaPad — Outstanding Balances Report</h2>
    <p>Generated on: {{ now()->format('d M Y H:i') }}</p>
    <p><strong>Total Outstanding: KES {{ number_format($totalOutstanding, 2) }}</strong></p>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Tenant</th>
                <th>Phone</th>
                <th>Property</th>
                <th>Unit</th>
                <th>Period</th>
                <th>Amount Due</th>
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
                <td>{{ $r->tenant->phone }}</td>
                <td>{{ $r->property->name }}</td>
                <td>{{ $r->unit->unit_number }}</td>
                <td>{{ $r->monthName() }} {{ $r->year }}</td>
                <td>KES {{ number_format($r->amount_due, 2) }}</td>
                <td>KES {{ number_format($r->amount_paid, 2) }}</td>
                <td><strong>KES {{ number_format($r->balance(), 2) }}</strong></td>
                <td>{{ ucfirst($r->status) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">KejaPad Property Management System</div>
</body>
</html>