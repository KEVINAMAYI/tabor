<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Student Statement</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 13px;
            color: #1f2937;
            margin: 30px;
        }

        .header,
        .meta-section,
        .summary-section {
            width: 100%;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0 0 6px;
            font-size: 22px;
        }

        .header p {
            margin: 2px 0;
            color: #4b5563;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 8px;
            text-transform: uppercase;
            color: #111827;
        }

        .meta-table,
        .ledger-table,
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table td {
            padding: 6px 8px;
            vertical-align: top;
        }

        .ledger-table th,
        .ledger-table td,
        .summary-table th,
        .summary-table td {
            border: 1px solid #d1d5db;
            padding: 8px;
        }

        .ledger-table th,
        .summary-table th {
            background: #f3f4f6;
            text-align: left;
        }

        .text-end {
            text-align: right;
        }

        .muted {
            color: #6b7280;
        }

        .footer {
            margin-top: 30px;
            font-size: 11px;
            color: #6b7280;
        }

        @media print {
            .no-print {
                display: none;
            }

            body {
                margin: 12mm;
            }
        }
    </style>
</head>

<body>

    <div style="text-align: center; margin-bottom: 20px;">
        <img src="{{ public_path('assets/images/logos/tabor_logo.png') }}" alt="Company Logo" style="height: 120px;">
        <h2 style="margin: 10px 0 4px; color: #0e334e; letter-spacing: 0.4px; font-size: 20px;">
        <p style="font-size: 13px; color: #666; margin: 0;">Student Fee Statement</p></h2>
    </div>

    <div class="header">
        <h1>Tabor Training Institute</h1>
        <p>P.O. Box [Insert Address]</p>
        <p>Phone: [Insert Phone] | Email: [Insert Email]</p>
        <p class="muted">Student Financial Statement</p>
    </div>

    <div class="meta-section">
        <div class="section-title">Student Details</div>
        <table class="meta-table">
            <tr>
                <td><strong>Name:</strong> {{ $statement['student']->first_name ?? '' }}
                    {{ $statement['student']->last_name ?? ($statement['student']->name ?? '') }}</td>
                <td><strong>Student No:</strong>
                    {{ 'TTI/' . ($statement['student']->admission_number . '/' . $statement['student']->created_at->format('Y')) }}
                </td>
            </tr>
            <tr>
                <td><strong>Trimester:</strong> {{ $statement['trimester']->name ?? '—' }}</td>
                <td><strong>Academic Year:</strong> {{ $statement['trimester']->academicYear->name ?? '—' }}</td>
            </tr>
            <tr>
                <td><strong>Period:</strong>
                    {{ optional($statement['trimester']->starts_at ?? $statement['trimester']->start_date)->format('d M Y') ?? '—' }}
                </td>
                <td><strong>To:</strong>
                    {{ optional($statement['trimester']->ends_at ?? $statement['trimester']->end_date)->format('d M Y') ?? '—' }}
                </td>
            </tr>
        </table>
    </div>

    <div class="summary-section">
        <div class="section-title">Statement Summary</div>
        <table class="summary-table">
            <tr>
                <th>Opening Balance</th>
                <th>Total Debits</th>
                <th>Total Credits</th>
                <th>Closing Balance</th>
            </tr>
            <tr>
                <td class="text-end"> {{ number_format($statement['opening_balance'], 2) }}</td>
                <td class="text-end"> {{ number_format($statement['total_debits'], 2) }}</td>
                <td class="text-end"> {{ number_format($statement['total_credits'], 2) }}</td>
                <td class="text-end"> {{ number_format($statement['closing_balance'], 2) }}</td>
            </tr>
        </table>
    </div>

    <div>
        <div class="section-title">Ledger</div>
        <table class="ledger-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Ref</th>
                    <th>Description</th>
                    <th>DR</th>
                    <th>CR</th>
                    <th class="text-end">Balance</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ optional($statement['trimester']->starts_at ?? $statement['trimester']->start_date)->format('d M Y') ?? '—' }}
                    </td>
                    <td>OPENING</td>
                    <td>Balance Brought Forward</td>
                    <td class="text-end">{{ number_format(0, 2) }}</td>
                    <td class="text-end">{{ number_format(0, 2) }}</td>
                    <td class="text-end">{{ number_format($statement['opening_balance'], 2) }}</td>
                </tr>

                @forelse($statement['ledger'] as $entry)
                    <tr>
                        <td>{{ optional($entry['date'])->format('d M Y') ?? '—' }}</td>
                        <td>{{ $entry['reference'] }}</td>
                        <td>{{ $entry['description'] }}</td>
                        <td class="text-end">
                            {{ $entry['dr'] > 0 ? number_format($entry['dr'], 2) : '' }}
                        </td>
                        <td class="text-end">
                            {{ $entry['cr'] > 0 ? number_format($entry['cr'], 2) : '' }}
                        </td>
                        <td class="text-end">{{ number_format($entry['balance'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center muted">No ledger entries found for this trimester.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer">
        Generated on {{ now()->format('d M Y H:i') }}.
    </div>
</body>

</html>
