<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Outstanding Balances Report</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            margin: 20px;
        }

        .header {
            display: flex;
            flex-direction: column;
            align-items: center;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 25px;
            margin-bottom: 25px;
            text-align: center;
        }

        .header-content h1 {
            font-size: 24px;
            margin: 0;
            color: #2c3e50;
        }

        .header-content .meta {
            font-size: 13px;
            color: #7f8c8d;
            margin-top: 4px;
        }

        .summary {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .summary div {
            font-size: 13px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 8px 12px;
            font-size: 12px;
        }

        th {
            background: #2c3e50;
            color: #fff;
            text-align: left;
        }

        tr:nth-child(even) {
            background: #f9f9f9;
        }

        tfoot td {
            font-weight: bold;
            background: #eef2f7;
        }
    </style>
</head>
<body>
<div class="header">
    @php
        $logoPath = public_path('assets/images/logos/tabor_logo.png');
        $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
    @endphp

    @if(empty($isExcel))
        <img src="{{ $logoBase64 }}" alt="Logo" width="120">
    @endif

    <div class="header-content">
        <h1>{{ $title }}</h1>
        <div class="meta">Generated on {{ $date }}</div>
    </div>
</div>

<div class="summary">
    <div>Total Outstanding: <strong>KES {{ number_format($summary['total_outstanding'], 2) }}</strong></div>
    <div>Affected Students: <strong>{{ $summary['affected_students'] }}</strong></div>
    <div>Affected Enrollments: <strong>{{ $summary['affected_enrollments'] }}</strong></div>
    <div>Average Outstanding: <strong>KES {{ number_format($summary['average_outstanding'], 2) }}</strong></div>
</div>

<table>
    <thead>
    <tr>
        <th>Admission No.</th>
        <th>Student</th>
        <th>Course</th>
        <th>Phone</th>
        <th>Email</th>
        <th>Oldest Due Date</th>
        <th>Outstanding Items</th>
        <th>Total Outstanding (KES)</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($rows as $row)
        <tr>
            <td>{{ $row->admission_number ?? 'N/A' }}</td>
            <td>{{ trim($row->first_name . ' ' . $row->last_name) }}</td>
            <td>{{ $row->course_title }}</td>
            <td>{{ $row->phone ?? 'N/A' }}</td>
            <td>{{ $row->email ?? 'N/A' }}</td>
            <td>{{ $row->oldest_due_date ? \Carbon\Carbon::parse($row->oldest_due_date)->format('d/m/Y') : 'N/A' }}</td>
            <td>{{ $row->items_count }}</td>
            <td>{{ number_format($row->total_outstanding, 2) }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="8" class="text-center">No outstanding balances found.</td>
        </tr>
    @endforelse
    </tbody>
    <tfoot>
    <tr>
        <td colspan="7">Total</td>
        <td>{{ number_format($summary['total_outstanding'], 2) }}</td>
    </tr>
    </tfoot>
</table>
</body>
</html>
