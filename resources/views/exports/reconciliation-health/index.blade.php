<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Reconciliation Health Report</title>
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
            flex-wrap: wrap;
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
    <div>Students Checked: <strong>{{ $summary['students_checked'] }}</strong></div>
    <div>Students With Mismatches: <strong>{{ $summary['students_with_mismatches'] }}</strong></div>
    <div>Total Mismatches: <strong>{{ $summary['total_mismatches'] }}</strong></div>
</div>

<h3>By Mismatch Type</h3>
<table>
    <thead>
    <tr>
        <th>Type</th>
        <th>Count</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($summary['by_type'] as $type => $count)
        <tr>
            <td>{{ $type }}</td>
            <td>{{ $count }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="2" class="text-center">No mismatches found.</td>
        </tr>
    @endforelse
    </tbody>
</table>

<h3>Affected Students</h3>
<table>
    <thead>
    <tr>
        <th>Admission No.</th>
        <th>Student</th>
        <th>Mismatch Count</th>
        <th>Types</th>
        <th>Total Drift (KES)</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($studentBreakdown as $row)
        <tr>
            <td>{{ $row->admission_number }}</td>
            <td>{{ $row->student_name }}</td>
            <td>{{ $row->mismatch_count }}</td>
            <td>{{ $row->types }}</td>
            <td>{{ number_format($row->total_drift, 2) }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="5" class="text-center">No affected students found.</td>
        </tr>
    @endforelse
    </tbody>
</table>
</body>
</html>
