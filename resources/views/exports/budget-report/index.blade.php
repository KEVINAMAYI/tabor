<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Budget vs Actual</title>
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

        tr.over-budget {
            background: #fdecea;
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

<table>
    <thead>
    <tr>
        <th>Vote Head</th>
        <th>Sub Vote Head</th>
        <th class="num">Budgeted</th>
        <th class="num">Actual</th>
        <th class="num">Variance</th>
        <th class="num">% Used</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($rows as $row)
        <tr class="{{ $row->over_budget ? 'over-budget' : '' }}">
            <td>{{ $row->vote_head }}</td>
            <td>{{ $row->sub_vote_head ?? '— (whole vote head)' }}</td>
            <td class="num">{{ number_format($row->budgeted_amount, 2) }}</td>
            <td class="num">{{ number_format($row->actual_amount, 2) }}</td>
            <td class="num">{{ number_format($row->variance, 2) }}</td>
            <td class="num">{{ $row->percent_used !== null ? $row->percent_used . '%' : '—' }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="6" style="text-align:center">No budget lines for this financial year.</td>
        </tr>
    @endforelse
    </tbody>
    <tfoot>
    <tr>
        <td colspan="2">Totals</td>
        <td class="num">{{ number_format($totals->total_budgeted, 2) }}</td>
        <td class="num">{{ number_format($totals->total_actual, 2) }}</td>
        <td class="num">{{ number_format($totals->total_budgeted - $totals->total_actual, 2) }}</td>
        <td></td>
    </tr>
    </tfoot>
</table>
</body>
</html>
