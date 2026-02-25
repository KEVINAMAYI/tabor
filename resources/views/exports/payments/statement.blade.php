<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Fee Statement</title>
</head>

<body style="background:#f2f4f7; padding: 18px;">

    @php
        // ---------- Statement Meta ----------
        $statementNo = $statementNo ?? 'STMT-' . str_pad((int) data_get($enrollment, 'id', 0), 5, '0', STR_PAD_LEFT);
        $statementDate = $statementDate ?? now()->format('d M Y');

        // Term/Period display (you already pass $trimester and $count)
        $termLabel = isset($trimester, $count) ? $trimester->trimester_number . '/' . $count : 'N/A';

        // Opening balance (optional)
        $opening = (float) ($openingBalance ?? 0);

        // ---------- Student Info ----------
        $studentName = data_get($enrollment, 'student.user.name') ?? (data_get($enrollment, 'student.name') ?? 'N/A');

        $studentId =
            'TTI/' .
            (data_get($enrollment, 'course.code') ?? 'N/A') .
            '/' .
            (data_get($enrollment, 'student.admission_number') ?? 'N/A') .
            '/' .
            (optional(data_get($enrollment, 'student.created_at'))->format('Y') ?? now()->format('Y'));

        $courseTitle = $enrollment->course->title . ' - ' . $enrollment->course->level;

        // ---------- Ledger: compute balances on ASC, display DESC ----------
        $fmtMoney = fn($n) => number_format((float) $n, 2);
        $fmtDate = fn($d) => \Carbon\Carbon::parse($d)->format('d-m-Y');

        $base = collect($ledger ?? []);

        // Ensure ASC for correct running balances
        $asc = $base->sortBy(fn($r) => \Carbon\Carbon::parse(data_get($r, 'date')))->values();

        $running = $opening;
        $totalCharges = 0.0;
        $totalPayments = 0.0;

        $withBalance = $asc->map(function ($r) use (&$running, &$totalCharges, &$totalPayments) {
            $amount = (float) data_get($r, 'amount', 0);
            $type = data_get($r, 'type', '');

            $charge = $type === 'charge' ? $amount : 0.0;
            $payment = $type === 'payment' ? $amount : 0.0;

            $totalCharges += $charge;
            $totalPayments += $payment;

            // Balance: charges increase owing, payments reduce owing
            $running = $running + $charge - $payment;

            $r['charge'] = $charge;
            $r['payment'] = $payment;
            $r['balance'] = $running;

            return $r;
        });

        // Display newest first, but keep the correct balance values
        $rowsToDisplay = $withBalance->sortBy(fn($r) => \Carbon\Carbon::parse(data_get($r, 'date')))->values();

        $finalBalance = $withBalance->last()['balance'] ?? $opening;
    @endphp

    <div id="statement" class="receipt-print">
        <div
            style="max-width: 650px; margin: auto; font-family: 'Segoe UI', Tahoma, sans-serif; padding: 25px; border: 1px solid #bbb; background-color: #fff; border-radius: 6px; box-shadow: 0 2px 6px rgba(0,0,0,0.05);">

            <!-- Header -->
            <div style="text-align: center; margin-bottom: 20px;">
                <img src="{{ asset('assets/images/logos/tabor_logo.png') }}" alt="Company Logo" style="height: 70px;">
                <h2 style="margin: 10px 0 4px; color: #0e334e; letter-spacing: 0.4px; font-size: 20px;">
                    Tabor Training Institute
                </h2>
                <p style="font-size: 13px; color: #666; margin: 0;">Student Fee Statement</p>
            </div>

            <hr style="border: none; border-top: 2px solid #0e334e; margin: 15px 0 25px;">

            <!-- Statement Info (match receipt style) -->
            <table style="width: 100%; font-size: 13px; border-collapse: collapse; margin-bottom: 15px;">
                <tr>
                    <td style="color: #555;">Statement No:</td>
                    <td style="text-align: right;"><strong>{{ $statementNo }}</strong></td>
                </tr>
                <tr>
                    <td style="color: #555;">Statement Date:</td>
                    <td style="text-align: right;"><strong>{{ $statementDate }}</strong></td>
                </tr>
                <tr>
                    <td style="color: #555;">Term / Period:</td>
                    <td style="text-align: right;"><strong>{{ $termLabel }}</strong></td>
                </tr>
            </table>

            <!-- Section: Student Info -->
            <div style="border: 1px solid #ddd; border-radius: 6px; margin-bottom: 20px;">
                <div style="background: #f8f9fb; padding: 10px 15px; border-bottom: 1px solid #ddd;">
                    <h3 style="color: #0e334e; margin: 0; font-size: 15px; text-align:center;">Student Information</h3>
                </div>
                <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                    <tr>
                        <td style="padding: 8px 15px; border-bottom: 1px solid #eee;"><strong>Student Name:</strong>
                        </td>
                        <td style="padding: 8px 15px; border-bottom: 1px solid #eee;">{{ $studentName }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 15px; border-bottom: 1px solid #eee;"><strong>Student ID:</strong></td>
                        <td style="padding: 8px 15px; border-bottom: 1px solid #eee;">{{ $studentId }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 15px; border-bottom: 1px solid #eee;"><strong>Course / Stream:</strong>
                        </td>
                        <td style="padding: 8px 15px; border-bottom: 1px solid #eee;">{{ $courseTitle }}</td>
                    </tr>
                </table>
            </div>

            <!-- Section: Fee Statement -->
            <div style="border: 1px solid #ddd; border-radius: 6px;">
                <div style="background: #f8f9fb; padding: 10px 15px; border-bottom: 1px solid #ddd;">
                    <h3 style="color: #0e334e; margin: 0; font-size: 15px;">Fee Statement</h3>
                </div>

                <div style="padding: 12px 15px;">
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 13px; min-width: 560px;">
                            <thead>
                                <tr>
                                    <th
                                        style="text-align: left; padding: 10px 8px; border-bottom: 1px solid #ddd; color: #0e334e;">
                                        Date</th>
                                    <th
                                        style="text-align: left; padding: 10px 8px; border-bottom: 1px solid #ddd; color: #0e334e;">
                                        Description</th>
                                    <th
                                        style="text-align: right; padding: 10px 8px; border-bottom: 1px solid #ddd; color: #0e334e;">
                                        Charges</th>
                                    <th
                                        style="text-align: right; padding: 10px 8px; border-bottom: 1px solid #ddd; color: #0e334e;">
                                        Payments</th>
                                    <th
                                        style="text-align: right; padding: 10px 8px; border-bottom: 1px solid #ddd; color: #0e334e;">
                                        Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Optional opening balance row --}}
                                @if ($opening != 0)
                                    <tr>
                                        <td style="padding: 9px 8px; border-bottom: 1px solid #eee;">
                                            {{ isset($trimester) ? $fmtDate($trimester->start_date) : $fmtDate(now()) }}
                                        </td>
                                        <td style="padding: 9px 8px; border-bottom: 1px solid #eee;">Opening balance
                                        </td>
                                        <td style="padding: 9px 8px; border-bottom: 1px solid #eee; text-align: right;">
                                        </td>
                                        <td style="padding: 9px 8px; border-bottom: 1px solid #eee; text-align: right;">
                                        </td>
                                        <td style="padding: 9px 8px; border-bottom: 1px solid #eee; text-align: right;">
                                            {{ $fmtMoney($opening) }}</td>
                                    </tr>
                                @endif

                                @forelse($rowsToDisplay as $row)
                                    <tr>
                                        <td style="padding: 9px 8px; border-bottom: 1px solid #eee;">
                                            {{ $fmtDate(data_get($row, 'date')) }}</td>
                                        <td style="padding: 9px 8px; border-bottom: 1px solid #eee;">
                                            {{ ucfirst($row['description']) }}</td>
                                        <td style="padding: 9px 8px; border-bottom: 1px solid #eee; text-align: right;">
                                            {{ data_get($row, 'charge') ? $fmtMoney(data_get($row, 'charge')) : '' }}
                                        </td>
                                        <td style="padding: 9px 8px; border-bottom: 1px solid #eee; text-align: right;">
                                            {{ data_get($row, 'payment') ? $fmtMoney(data_get($row, 'payment')) : '' }}
                                        </td>
                                        <td style="padding: 9px 8px; border-bottom: 1px solid #eee; text-align: right;">
                                            {{ $fmtMoney(data_get($row, 'balance', 0)) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5"
                                            style="padding: 12px 8px; border-bottom: 1px solid #eee; color:#777; text-align:center;">
                                            No statement entries available.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Summary strip -->
                    <div style="margin-top: 14px; border-top: 1px dashed #ccc; padding-top: 12px;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                            <tr>
                                <td style="color: #555;">Total Charges:</td>
                                <td style="text-align: right; font-weight: 600;">KES {{ $fmtMoney($totalCharges) }}
                                </td>
                            </tr>
                            <tr>
                                <td style="color: #555;">Total Payments:</td>
                                <td style="text-align: right; font-weight: 600;">KES {{ $fmtMoney($totalPayments) }}
                                </td>
                            </tr>
                            <tr>
                                <td style="color: #555;"><strong>Balance Owing:</strong></td>
                                <td style="text-align: right; color: #0e334e; font-weight: 800; font-size: 14px;">
                                    KES {{ $fmtMoney($finalBalance) }}
                                </td>
                            </tr>
                        </table>
                    </div>

                </div>
            </div>

            <hr style="border: none; border-top: 1px dashed #ccc; margin: 25px 0;">

            <!-- Footer -->
            <div style="text-align: center; font-size: 13px; color: #555;">
                <p>This statement is generated as <strong>official fee account information</strong>.</p>
                <p style="font-size: 12px; color: #888; margin-top: 8px;">
                    For assistance, contact
                    <a href="mailto:office@tabor.ac.ke"
                        style="color: #0e334e; text-decoration: none;">office@tabor.ac.ke</a>
                </p>
                <p style="font-size: 12px; color: #aaa; margin-top: 10px;">
                    &copy; {{ date('Y') }} Tabor Training Institute. All Rights Reserved.
                </p>
            </div>
        </div>
    </div>

    {{-- Uncomment if you want auto print --}}
    {{-- <script>
    window.addEventListener('load', () => window.print());
</script> --}}

</body>

</html>
