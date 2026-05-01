<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Student Fee Statement</title>

    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 13px;
            color: #1f2937;
            margin: 30px;
        }

        .no-print {
            margin-bottom: 20px;
        }

        .institution-header {
            text-align: center;
            margin-bottom: 22px;
        }

        .institution-header img {
            height: 70px;
            margin-bottom: 8px;
        }

        .institution-header h1 {
            margin: 0;
            font-size: 22px;
            color: #0e334e;
            letter-spacing: 0.4px;
        }

        .institution-header p {
            margin: 3px 0;
            color: #666;
            font-size: 12px;
        }

        .document-title {
            margin-top: 10px;
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
            color: #111827;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 18px 0 8px;
            color: #111827;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table td {
            padding: 6px 8px;
            border: 1px solid #e5e7eb;
            vertical-align: top;
        }

        .summary-table th,
        .summary-table td,
        .ledger-table th,
        .ledger-table td {
            border: 1px solid #d1d5db;
            padding: 8px;
        }

        .summary-table th,
        .ledger-table th {
            background: #f3f4f6;
            text-align: left;
        }

        .text-end {
            text-align: right;
        }

        .muted {
            color: #6b7280;
        }

        .balance-due {
            font-weight: bold;
            color: #b91c1c;
        }

        .footer {
            margin-top: 30px;
            font-size: 11px;
            color: #6b7280;
            display: flex;
            justify-content: space-between;
        }

        .allocation-row td {
            font-size: 11px;
            background: #fafafa;
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
            <p style="font-size: 13px; color: #666; margin: 0;">Student Fee Statement</p>
        </h2>
    </div>
    <div class="institution-header">
        <p>Phone: +254 798 496129, +254 726 241095 | Email: office@tabor.ac.ke</p>
        <p>Website: www.tabor.ac.ke | Location: Showbe Plaza, Pangani, Thika Highway, Nairobi, Kenya</p>
    </div>

    <div class="section-title">Student & Enrollment Details</div>

    <table class="meta-table">
        <tr>
            <td>
                <strong>Student Name:</strong>
                {{ trim(($statement['student']->first_name ?? '') . ' ' . ($statement['student']->last_name ?? '')) ?: $statement['student']->name ?? '—' }}
            </td>
            <td>
                <strong>Student No:</strong>
                {{ 'TTI/' . ($statement['student']->admission_number . '/' . $statement['course']->code . '/' . $statement['student']->created_at->format('Y')) }}
            </td>
        </tr>

        <tr>
            <td>
                <strong>Course:</strong>
                {{ $statement['course']->title . ' - ' . $statement['course']->level }}
            </td>
            <td>
                <strong>Trimester:</strong>
                {{ $statement['trimester']->name . '/' . $statement['academic_year']->name }}
            </td>
        </tr>

        <tr>
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
                {{ 'Trimester ' . $statement['progression']->trimester_sequence . ' of ' . $statement['course']->number_of_trimesters }}
            </td>
            <td>
                <strong>Progression Status:</strong>
                {{ ucfirst($statement['progression']->status) }}
            </td>
            <td>
            </td>
        </tr>
    </table>

    <div class="section-title">Statement Summary</div>

    <table class="summary-table">
        <thead>
            <tr>
                <th>Balance B/F</th>
                <th>Total DR</th>
                <th>Total CR</th>
                <th>Balance C/F</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-end">{{ number_format($statement['opening_balance'], 2) }}</td>
                <td class="text-end">{{ number_format($statement['total_debits'], 2) }}</td>
                <td class="text-end">{{ number_format($statement['total_credits'], 2) }}</td>
                <td class="text-end balance-due">{{ number_format($statement['closing_balance'], 2) }}</td>
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
                <td>{{ optional($statement['start_date'])->format('d M Y') ?? '' }}</td>
                <td>B/F</td>
                <td>Balance Brought Forward</td>
                <td class="text-end"></td>
                <td class="text-end"></td>
                <td class="text-end">{{ number_format($statement['opening_balance'], 2) }}</td>
            </tr>

            @forelse($statement['ledger'] as $entry)
                <tr>
                    <td>{{ optional($entry['date'])->format('d M Y') ?? '—' }}</td>
                    <td>{{ $entry['reference'] }}</td>
                    <td>{{ $entry['description'] }}</td>
                    <td class="text-end">
                        {{ $entry['dr'] > 0 ? number_format($entry['dr'], 2) : '—' }}
                    </td>
                    <td class="text-end">
                        {{ $entry['cr'] > 0 ? number_format($entry['cr'], 2) : '—' }}
                    </td>
                    <td class="text-end">
                        {{ number_format($entry['balance'], 2) }}
                    </td>
                </tr>

                @if (($entry['source_type'] ?? null) === 'payment' && !empty($entry['allocations']))
                    @foreach ($entry['allocations'] as $allocation)
                        <tr class="allocation-row">
                            <td></td>
                            <td></td>
                            <td style="padding-left: 24px; color: #6b7280;">
                                ↳ Allocated to {{ $allocation['description'] }}
                            </td>
                            <td class="text-end">—</td>
                            <td class="text-end" style="color: #6b7280;">
                                {{ number_format($allocation['amount'], 2) }}
                            </td>
                            <td></td>
                        </tr>
                    @endforeach
                @endif
            @empty
                <tr>
                    <td colspan="6" class="text-end muted">
                        No ledger entries for this trimester.
                    </td>
                </tr>
            @endforelse

            <tr>
                <th colspan="3" class="text-end">Totals</th>
                <th class="text-end">{{ number_format($statement['total_debits'], 2) }}</th>
                <th class="text-end">{{ number_format($statement['total_credits'], 2) }}</th>
                <th class="text-end">{{ number_format($statement['closing_balance'], 2) }}</th>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <div>Prepared by: __________________________</div>
        <div>Approved by: __________________________</div>
    </div>
</body>

</html>
