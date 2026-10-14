<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Maintenance Report</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #333; }
        h2 { color: #5926C8; margin-bottom: 4px; }
        p { margin: 2px 0; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background: #5926C8; color: white; padding: 7px 6px; text-align: left; }
        td { padding: 6px; border-bottom: 1px solid #eee; }
        tr:nth-child(even) { background: #f9f9f9; }
        .footer { margin-top: 20px; font-size: 10px; color: #999; text-align: right; }
    </style>
</head>
<body>
    <h2>KejaPad — Maintenance Report</h2>
    <p>Generated on: {{ now()->format('d M Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Title</th>
                <th>Tenant</th>
                <th>Property</th>
                <th>Unit</th>
                <th>Category</th>
                <th>Priority</th>
                <th>Assigned To</th>
                <th>Status</th>
                <th>Submitted</th>
            </tr>
        </thead>
        <tbody>
            @foreach($requests as $i => $req)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $req->title }}</td>
                <td>{{ $req->tenant->full_name }}</td>
                <td>{{ $req->property->name }}</td>
                <td>{{ $req->unit->unit_number }}</td>
                <td>{{ ucfirst($req->category) }}</td>
                <td>{{ ucfirst($req->priority) }}</td>
                <td>{{ $req->assignedTo?->name ?? '—' }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $req->status)) }}</td>
                <td>{{ $req->created_at->format('d M Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">KejaPad Property Management System</div>
</body>
</html>