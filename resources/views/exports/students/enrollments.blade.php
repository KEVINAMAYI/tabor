<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Active Enrollments</title>

    <base href="{{ URL::to('/') }}">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            margin: 20px;
        }

        .header {
            display: flex;
            flex-direction: column; /* stack logo + content */
            align-items: center; /* center horizontally */
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 25px; /* more space below */
            margin-bottom: 25px;
            text-align: center;
        }

        .header-logo {
            max-width: 120px;
            max-height: 120px;
            width: auto;
            height: auto;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 10px;
        }

        .header-logo-placeholder {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: #8E44AD;
            color: white;
            font-size: 2rem;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 10px;
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

        .header-org-name {
            width: 100%;
            padding: 15px;
            color: #2c3e50;
            font-size: 1.2rem;
            font-weight: bold;
            text-align: center;
            margin-bottom: 10px;
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
        <div class="meta">Generated on {{ now()->format('d M Y, H:i') }}</div>
    </div>
</div>
<table class="table search-table align-middle text-nowrap">
    <thead class="header-item">
    <tr>
        <th>Enrollment ID</th>
        <th>Name</th>
        <th>Course</th>
        <th>Intake</th>
        <th>Paid Amount</th>
        <th>Balance</th>
        <th>Approved On</th>
        <th>Action</th>
    </tr>
    </thead>
    <tbody>

    @forelse ($enrollments as $enrollment)
        <tr class="search-items">
            <td>{{ 'TTI/' . $enrollment->student?->admission_number . '/' . $enrollment->course?->code . '/' . $enrollment->created_at->format('Y') }}
            </td>
            <td>{{ $enrollment->student?->first_name }} {{ $enrollment->student?->last_name }}
            </td>
            <td>{{ $enrollment->course?->title }}</td>
            <td>{{ $enrollment->intake?->name }}</td>
            <td>{{ number_format($enrollment->payments->sum('amount'), 2) }}</td>
            <td>{{ number_format($enrollment->course?->price - $enrollment->payments?->sum('amount'), 2) }}</td>
            <td>{{ $enrollment->created_at->format('d-m-Y') }}</td>
            <td>
                <div class="action-btn dropdown">
                    <a href="#" class="text-primary dropdown-toggle" id="studentActions"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="ti ti-dots-vertical fs-5"></i>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="studentActions">

                        @can('edit-students')
                            <li>
                                <a href="javascript:void(0)"
                                   {{-- wire:click="editStatus({{ $enrollment->id }})" --}}
                                   class="dropdown-item">
                                    <i class="ti ti-eye fs-5 me-2"></i> View
                                </a>
                            </li>
                        @endcan
                    </ul>
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="9" class="text-center">No Enrollments found.</td>
        </tr>
    @endforelse
    </tbody>
</table>
</body>
</html>
