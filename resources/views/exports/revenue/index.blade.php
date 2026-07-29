<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Revenue &amp; Collections Report</title>
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
            margin-bottom: 20px;
        }

        .summary div {
            font-size: 13px;
        }

        h3 {
            font-size: 15px;
            color: #2c3e50;
            margin-top: 25px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
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
        <div class="meta">{{ $rangeLabel }} &middot; Generated on {{ $date }}</div>
    </div>
</div>

<div class="summary">
    <div>Total Collected: <strong>KES {{ number_format($summary['total_collected'], 2) }}</strong></div>
    <div>Transactions: <strong>{{ $summary['transaction_count'] }}</strong></div>
    <div>Average Payment: <strong>KES {{ number_format($summary['average_payment'], 2) }}</strong></div>
</div>

<h3>By Course</h3>
<table>
    <thead>
    <tr>
        <th>Course</th>
        <th>Transactions</th>
        <th>Total (KES)</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($courseBreakdown as $row)
        <tr>
            <td>{{ $row->course_title }}</td>
            <td>{{ $row->txn_count }}</td>
            <td>{{ number_format($row->total, 2) }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="3" class="text-center">No payments found.</td>
        </tr>
    @endforelse
    </tbody>
</table>

<h3>By Payment Method</h3>
<table>
    <thead>
    <tr>
        <th>Method</th>
        <th>Transactions</th>
        <th>Total (KES)</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($methodBreakdown as $row)
        <tr>
            <td>{{ ucfirst($row->method_name ?? 'N/A') }}</td>
            <td>{{ $row->txn_count }}</td>
            <td>{{ number_format($row->total, 2) }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="3" class="text-center">No payments found.</td>
        </tr>
    @endforelse
    </tbody>
</table>
</body>
</html>
