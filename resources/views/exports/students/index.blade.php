<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Students List</title>

    <base href="{{ URL::to('/') }}">

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            margin: 20px;
        }

        .header {
            display: flex;
            flex-direction: column;
            /* stack logo + content */
            align-items: center;
            /* center horizontally */
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 25px;
            /* more space below */
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

        th,
        td {
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

        @if (empty($isExcel))
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
                <th>Admission Number</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone Number</th>
                <th>ID Number</th>
            </tr>
        </thead>
        <tbody>

            @forelse ($students as $student)
                <tr class="search-items align-middle" style="border-bottom: 1px solid #e5e7eb;">
                    <!-- Admission Number -->
                    <td class="text-uppercase fw-semibold" style="color: #f69122;">
                        {{ 'TTI/' . $student->admission_number . '/' . $student->created_at->format('Y') }}
                    </td>

                    <!-- Full Name -->
                    <td class="fw-semibold" style="color: #0e334f;">
                        {{ $student->first_name }} {{ $student->last_name }}
                    </td>

                    <!-- Email -->
                    <td>
                        <span class="badge bg-light text-dark px-2 py-1 rounded-pill">
                            {{ $student->email }}
                        </span>
                    </td>

                    <!-- Phone -->
                    <td>
                        <span class="text-muted">{{ $student->phone }}</span>
                    </td>

                    <!-- ID Number -->
                    <td>
                        <span class="text-muted">{{ $student->id_number }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">No students found.</td>
                </tr>
            @endforelse

        </tbody>
    </table>
</body>

</html>
