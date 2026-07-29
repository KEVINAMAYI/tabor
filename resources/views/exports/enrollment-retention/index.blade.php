<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Enrollment &amp; Retention Report</title>
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
    <div>Total Progressions: <strong>{{ $summary['total_progressions'] }}</strong></div>
    <div>Retained: <strong>{{ $summary['retained'] }}</strong></div>
    <div>Retention Rate: <strong>{{ $summary['retention_rate'] }}%</strong></div>
    <div>Deferred: <strong>{{ $summary['deferred'] }}</strong></div>
    <div>Repeated: <strong>{{ $summary['repeated'] }}</strong></div>
    <div>Cancelled: <strong>{{ $summary['cancelled'] }}</strong></div>
</div>

<h3>By Trimester Sequence</h3>
<table>
    <thead>
    <tr>
        <th>Trimester</th>
        <th>Total</th>
        <th>Retained</th>
        <th>Deferred</th>
        <th>Repeated</th>
        <th>Cancelled</th>
        <th>Retention Rate</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($trimesterBreakdown as $row)
        <tr>
            <td>T{{ $row->trimester_sequence }}</td>
            <td>{{ $row->total }}</td>
            <td>{{ $row->retained }}</td>
            <td>{{ $row->deferred }}</td>
            <td>{{ $row->repeated }}</td>
            <td>{{ $row->cancelled }}</td>
            <td>{{ $row->retention_rate }}%</td>
        </tr>
    @empty
        <tr>
            <td colspan="7" class="text-center">No progressions found.</td>
        </tr>
    @endforelse
    </tbody>
</table>

<h3>By Course</h3>
<table>
    <thead>
    <tr>
        <th>Course</th>
        <th>Total</th>
        <th>Retained</th>
        <th>Retention Rate</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($courseBreakdown as $row)
        <tr>
            <td>{{ $row->course_title }}</td>
            <td>{{ $row->total }}</td>
            <td>{{ $row->retained }}</td>
            <td>{{ $row->retention_rate }}%</td>
        </tr>
    @empty
        <tr>
            <td colspan="4" class="text-center">No progressions found.</td>
        </tr>
    @endforelse
    </tbody>
</table>
</body>
</html>
