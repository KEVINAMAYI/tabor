<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Student Fee Statement</title>

    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            color: #1f2937;
            margin: 28px;
        }

        .institution-header {
            text-align: center;
            margin-bottom: 18px;
        }

        .institution-header img {
            height: 100px;
            margin-bottom: 6px;
        }

        .institution-header h1 {
            margin: 0;
            font-size: 20px;
            color: #0e334e;
        }

        .institution-header p {
            margin: 3px 0;
            color: #666;
            font-size: 11px;
        }

        .document-title {
            margin-top: 6px;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            color: #111827;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 16px 0 8px;
            color: #111827;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table td,
        .summary-table th,
        .summary-table td,
        .ledger-table th,
        .ledger-table td {
            border: 1px solid #d1d5db;
            padding: 7px;
            vertical-align: top;
        }

        .summary-table th,
        .ledger-table th {
            background: #f3f4f6;
            text-align: left;
        }

        .text-end {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .muted {
            color: #6b7280;
        }

        .balance-due {
            font-weight: bold;
            color: #b91c1c;
        }

        .balance-cleared {
            font-weight: bold;
            color: #15803d;
        }

        .allocation-row td {
            font-size: 10.5px;
            background: #fafafa;
            color: #6b7280;
        }

        .footer {
            margin-top: 30px;
            font-size: 11px;
            color: #6b7280;
            display: flex;
            justify-content: space-between;
        }
    </style>
</head>

<body>
    @php
        $student = $statement['student'];
        $course = $statement['course'];
        $trimester = $statement['trimester'];
        $academicYear = $statement['academic_year'];
        $progression = $statement['progression'];

        $studentName = trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''));
        $studentNumber =
            'TTI/' .
            ($student->admission_number ?? '—') .
            '/' .
            ($course?->code ?? '—') .
            '/' .
            optional($student->created_at)->format('Y');

        $closingBalanceClass = ($statement['closing_balance'] ?? 0) > 0 ? 'balance-due' : 'balance-cleared';
    @endphp

    <div class="institution-header">
        <img src="{{ public_path('assets/images/logos/tabor_logo.png') }}" alt="Tabor Logo">

        <div class="document-title">Student Fee Statement</div>

        <p>Phone: +254 798 496129, +254 726 241095 | Email: office@tabor.ac.ke</p>
        <p>Website: www.tabor.ac.ke | Location: Showbe Plaza, Pangani, Thika Highway, Nairobi, Kenya</p>
    </div>

    <div class="section-title">Student & Enrollment Details</div>

    <table class="meta-table">
        <tr>
            <td>
                <strong>Student Name:</strong>
                {{ $studentName ?: $student->name ?? '—' }}
            </td>
            <td>
                <strong>Student No:</strong>
                {{ $studentNumber }}
            </td>
        </tr>

        <tr>
            <td>
                <strong>Course:</strong>
                {{ trim(($course?->title ?? '—') . ' - ' . ($course?->level ?? '')) }}
            </td>
            <td>
                <strong>Trimester:</strong>
                {{ $trimester?->name ?? '—' }}
                @if ($academicYear)
                    / {{ $academicYear->name }}
                @endif
            </td>
        </tr>

        <tr>
            <td>
                <strong>Period From:</strong>
                {{ optional($statement['start_date'])->format('d M Y') ?? '—' }}
            </td>
            <td>
                <strong>Period To:</strong>
                {{ optional($statement['end_date'])->format('d M Y') ?? '—' }}
            </td>
        </tr>

        <tr>
            <td>
                <strong>Progression:</strong>
                Trimester {{ $progression->trimester_sequence }}
                @if ($course?->number_of_trimesters)
                    of {{ $course->number_of_trimesters }}
                @endif
            </td>
            <td>
                <strong>Progression Status:</strong>
                {{ str_replace('_', ' ', ucfirst($progression->status)) }}
            </td>
        </tr>
    </table>

    <div class="section-title">Statement Summary</div>

    <table class="summary-table">
        <thead>
            <tr>
                <th>Balance B/F</th>
                <th>Charges This Trimester</th>
                <th>Payments This Trimester</th>
                <th>Balance C/F</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-end">{{ number_format($statement['opening_balance'], 2) }}</td>
                <td class="text-end">{{ number_format($statement['charge_total'], 2) }}</td>
                <td class="text-end">{{ number_format($statement['payment_total'], 2) }}</td>
                <td class="text-end {{ $closingBalanceClass }}">
                    {{ number_format($statement['closing_balance'], 2) }}
                </td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Ledger Statement Details</div>

    <table class="ledger-table">
        <thead>
            <tr>
                <th style="width: 12%;">Date</th>
                <th style="width: 14%;">Ref</th>
                <th>Description</th>
                <th style="width: 13%;" class="text-end">DR</th>
                <th style="width: 13%;" class="text-end">CR</th>
                <th style="width: 14%;" class="text-end">Balance</th>
            </tr>
        </thead>

        <tbody>
            <tr>
                <td>{{ optional($statement['start_date'])->format('d M Y') ?? '—' }}</td>
                <td>B/F</td>
                <td>Previous Balance Brought Forward</td>
                <td class="text-end"></td>
                <td class="text-end"></td>
                <td class="text-end">{{ number_format($statement['opening_balance'], 2) }}</td>
            </tr>

            @forelse($statement['ledger'] as $entry)
                <tr>
                    <td>{{ optional($entry['date'])->format('d M Y') ?? '—' }}</td>
                    <td>{{ $entry['reference'] ?? '—' }}</td>
                    <td>{{ $entry['description'] ?? '—' }}</td>
                    <td class="text-end">
                        {{ ($entry['dr'] ?? 0) > 0 ? number_format($entry['dr'], 2) : '' }}
                    </td>
                    <td class="text-end">
                        {{ ($entry['cr'] ?? 0) > 0 ? number_format($entry['cr'], 2) : '' }}
                    </td>
                    <td class="text-end">
                        {{ number_format($entry['balance'] ?? 0, 2) }}
                    </td>
                </tr>

                @if (($entry['source_type'] ?? null) === 'payment' && !empty($entry['allocations']))
                    @foreach ($entry['allocations'] as $allocation)
                        <tr class="allocation-row">
                            <td></td>
                            <td></td>
                            <td style="padding-left: 24px;">
                                Allocated to {{ $allocation['description'] ?? 'Fee Item' }}
                            </td>
                            <td class="text-end"></td>
                            <td class="text-end">
                                {{ number_format($allocation['amount'] ?? 0, 2) }}
                            </td>
                            <td></td>
                        </tr>
                    @endforeach
                @endif
            @empty
                <tr>
                    <td colspan="6" class="text-center muted">
                        No ledger entries for this progression.
                    </td>
                </tr>
            @endforelse

            <tr>
                <th colspan="3">Totals</th>
                <th>{{ number_format($statement['total_debits'], 2) }}</th>
                <th>{{ number_format($statement['total_credits'], 2) }}</th>
                <th>{{ number_format($statement['closing_balance'], 2) }}</th>
            </tr>
        </tbody>
    </table>
</body>

</html>
