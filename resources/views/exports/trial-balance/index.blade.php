<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Trial Balance</title>
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

        td.num, th.num {
            text-align: right;
        }

        tr:nth-child(even) {
            background: #f9f9f9;
        }

        tfoot td {
            font-weight: bold;
            background: #eef2f7;
        }

        .warning {
            margin-top: 15px;
            padding: 10px;
            border: 1px solid #e74c3c;
            background: #fdecea;
            color: #c0392b;
            font-size: 12px;
        }
    </style>
</head>
<body>
<div class="header">
    @php
        $logoPath = public_path('assets/images/logos/tabor_logo.png');
        $logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : null;
    @endphp

    @if(empty($isExcel) && $logoBase64)
        <img src="{{ $logoBase64 }}" alt="Logo" width="120">
    @endif

    <div class="header-content">
        <h1>{{ $title }}</h1>
        <div class="meta">Generated on {{ $date }}</div>
    </div>
</div>

@unless ($totals->balanced)
    <div class="warning">
        Warning: total debits ({{ number_format($totals->total_debit, 2) }}) do not equal total credits
        ({{ number_format($totals->total_credit, 2) }}).
    </div>
@endunless

<table>
    <thead>
    <tr>
        <th>Code</th>
        <th>Account</th>
        <th>Type</th>
        <th class="num">Total Debit</th>
        <th class="num">Total Credit</th>
        <th class="num">Closing Balance</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($rows as $row)
        <tr>
            <td>{{ $row->account_code }}</td>
            <td>{{ $row->name }}</td>
            <td>{{ ucfirst($row->account_type) }}</td>
            <td class="num">{{ number_format($row->total_debit, 2) }}</td>
            <td class="num">{{ number_format($row->total_credit, 2) }}</td>
            <td class="num">{{ number_format($row->closing_balance, 2) }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="6" style="text-align:center">No posted journal entries in this range.</td>
        </tr>
    @endforelse
    </tbody>
    <tfoot>
    <tr>
        <td colspan="3">Totals</td>
        <td class="num">{{ number_format($totals->total_debit, 2) }}</td>
        <td class="num">{{ number_format($totals->total_credit, 2) }}</td>
        <td></td>
    </tr>
    </tfoot>
</table>
</body>
</html>
