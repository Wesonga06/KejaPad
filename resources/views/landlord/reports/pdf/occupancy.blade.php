<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Occupancy Report</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #333; }
        h2 { color: #0f3460; margin-bottom: 4px; }
        p { margin: 2px 0; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background: #0f3460; color: white; padding: 7px 6px; text-align: left; }
        td { padding: 6px; border-bottom: 1px solid #eee; }
        tr:nth-child(even) { background: #f9f9f9; }
        .footer { margin-top: 20px; font-size: 10px; color: #999; text-align: right; }
    </style>
</head>
<body>
    <h2>KejaPad — Occupancy Report</h2>
    <p>Generated on: {{ now()->format('d M Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Property</th>
                <th>Unit</th>
                <th>Rent Amount</th>
                <th>Status</th>
                <th>Tenant</th>
                <th>Phone</th>
                <th>Move-in Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($units as $i => $u)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $u->property->name }}</td>
                <td>{{ $u->unit_number }}</td>
                <td>KES {{ number_format($u->rent_amount, 2) }}</td>
                <td>{{ ucfirst($u->status) }}</td>
                <td>{{ $u->tenant?->full_name ?? '—' }}</td>
                <td>{{ $u->tenant?->phone ?? '—' }}</td>
                <td>{{ $u->tenant?->move_in_date?->format('d M Y') ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">KejaPad Property Management System</div>
</body>
</html>