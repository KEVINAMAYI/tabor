<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Course-Application Funnel Report</title>
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
    <div>Submitted: <strong>{{ $summary['submitted'] }}</strong></div>
    <div>Approved: <strong>{{ $summary['approved'] }}</strong></div>
    <div>Rejected: <strong>{{ $summary['rejected'] }}</strong></div>
    <div>Pending: <strong>{{ $summary['pending'] }}</strong></div>
    <div>Conversion Rate: <strong>{{ $summary['conversion_rate'] }}%</strong></div>
    <div>Avg. Turnaround: <strong>{{ $summary['avg_turnaround_hours'] }} hrs</strong></div>
</div>

<h3>By Course</h3>
<table>
    <thead>
    <tr>
        <th>Course</th>
        <th>Submitted</th>
        <th>Approved</th>
        <th>Rejected</th>
        <th>Pending</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($courseBreakdown as $row)
        <tr>
            <td>{{ $row->course_title }}</td>
            <td>{{ $row->submitted }}</td>
            <td>{{ $row->approved }}</td>
            <td>{{ $row->rejected }}</td>
            <td>{{ $row->pending }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="5" class="text-center">No applications found.</td>
        </tr>
    @endforelse
    </tbody>
</table>

<h3>By Reviewer</h3>
<table>
    <thead>
    <tr>
        <th>Reviewer</th>
        <th>Reviewed</th>
        <th>Approved</th>
        <th>Rejected</th>
        <th>Avg. Turnaround (hrs)</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($reviewerBreakdown as $row)
        <tr>
            <td>{{ $row->reviewer_name }}</td>
            <td>{{ $row->reviewed_count }}</td>
            <td>{{ $row->approved_count }}</td>
            <td>{{ $row->rejected_count }}</td>
            <td>{{ $row->avg_hours ? number_format($row->avg_hours, 1) : 'N/A' }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="5" class="text-center">No reviewed applications found.</td>
        </tr>
    @endforelse
    </tbody>
</table>
</body>
</html>
