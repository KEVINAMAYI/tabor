<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Student Fee Statement</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11.5px;
            color: #1f2937;
            margin: 26px 30px;
        }

        /* Header */
        .hdr {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        .hdr td {
            vertical-align: top;
        }

        .hdr .logo {
            text-align: center;
        }

        .hdr .logo img {
            height: 80px;
            display: block;
            margin: 0 auto 4px;
        }

        .hdr .logo .title {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #111;
        }

        .hdr .logo .sub {
            font-size: 9.5px;
            color: #6b7280;
            margin-top: 2px;
        }

        .ref-box {
            border: 1px solid #d1d5db;
            padding: 8px 10px;
            font-size: 10px;
            text-align: right;
            white-space: nowrap;
        }

        .ref-box .lbl {
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #9ca3af;
        }

        .ref-box .val {
            font-weight: bold;
            color: #111;
            margin-bottom: 5px;
        }

        /* Meta */
        .section {
            font-size: 9.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #6b7280;
            border-bottom: 1px solid #e5e7eb;
            margin: 13px 0 6px;
            padding-bottom: 2px;
        }

        .meta {
            width: 100%;
            border-collapse: collapse;
        }

        .meta td {
            border: 1px solid #e5e7eb;
            padding: 5px 8px;
            width: 50%;
            vertical-align: top;
        }

        .meta .lbl {
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #9ca3af;
            display: block;
        }

        .meta .val {
            font-weight: 600;
            color: #111;
        }

        /* Ledger */
        .ledger {
            width: 100%;
            border-collapse: collapse;
        }

        .ledger th {
            background: #f3f4f6;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #6b7280;
            border: 1px solid #e5e7eb;
            padding: 5px 6px;
        }

        .ledger td {
            border: 1px solid #e5e7eb;
            padding: 5px 6px;
            vertical-align: top;
        }

        .r {
            text-align: right;
        }

        .c {
            text-align: center;
        }

        /* Row types */
        .row-bf td {
            background: #eff6ff;
            color: #1e40af;
            font-weight: 600;
        }

        .row-charge td {
            color: #111;
        }

        .row-pay td {
            color: #15803d;
        }

        .row-waiver td {
            color: #b45309;
        }

        .row-credit td {
            color: #6d28d9;
        }

        .row-alloc td {
            font-size: 9.5px;
            color: #9ca3af;
            background: #fafafa;
            border-top: none;
            padding: 2px 6px 2px 20px;
        }

        /* Totals row */
        .row-totals th {
            background: #1f2937;
            color: #fff;
            border-color: #1f2937;
            padding: 7px 6px;
            font-size: 10px;
        }

        .row-totals .bal-pos {
            color: #fca5a5;
        }

        .row-totals .bal-neg {
            color: #6ee7b7;
        }

        .row-totals .bal-zero {
            color: #d1d5db;
        }

        /* Running balance colors */
        .bal-pos {
            color: #b91c1c;
            font-weight: 600;
        }

        .bal-neg {
            color: #15803d;
            font-weight: 600;
        }

        .bal-zero {
            color: #374151;
        }

        /* Method badge */
        .badge {
            font-size: 8.5px;
            font-weight: bold;
            padding: 1px 4px;
            border-radius: 2px;
        }

        .m-mpesa {
            background: #dcfce7;
            color: #166534;
        }

        .m-bank {
            background: #dbeafe;
            color: #1e40af;
        }

        .m-cash {
            background: #fef9c3;
            color: #854d0e;
        }

        .m-credit {
            background: #ede9fe;
            color: #5b21b6;
        }

        .m-cheque {
            background: #f3f4f6;
            color: #374151;
        }

        /* Outstanding box */
        .box-due {
            margin-top: 16px;
            border: 1.5px solid #b91c1c;
            border-radius: 3px;
            padding: 10px 14px;
        }

        .box-due .hd {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: #b91c1c;
            margin-bottom: 4px;
        }

        .box-due .amt {
            font-size: 17px;
            font-weight: bold;
            color: #b91c1c;
            margin-bottom: 7px;
        }

        .box-due .pi {
            font-size: 10px;
            color: #374151;
            line-height: 1.7;
        }

        .box-due .pk {
            display: inline-block;
            min-width: 120px;
            font-weight: 600;
        }

        .box-ok {
            margin-top: 16px;
            border: 1.5px solid #15803d;
            border-radius: 3px;
            padding: 8px 14px;
            color: #15803d;
            font-weight: bold;
            text-align: center;
            font-size: 11px;
        }

        /* Footer */
        .footer {
            margin-top: 20px;
            border-top: 1px solid #e5e7eb;
            padding-top: 6px;
            font-size: 9.5px;
            color: #9ca3af;
            width: 100%;
            border-collapse: collapse;
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

        $name = trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''));
        $studNo =
            'TTI/' .
            ($student->admission_number ?? '-') .
            '/' .
            ($course?->code ?? '-') .
            '/' .
            optional($student->created_at)->format('Y');
        $stmtRef =
            'STMT-' .
            strtoupper($student->admission_number ?? $student->id) .
            '-T' .
            $progression->trimester_sequence .
            '-' .
            now()->format('Ymd');

        $opening = (float) ($statement['opening_balance'] ?? 0);
        $closing = (float) ($statement['closing_balance'] ?? 0);

        $bc = fn($v) => $v > 0 ? 'bal-pos' : ($v < 0 ? 'bal-neg' : 'bal-zero');
        $tbc = fn($v) => $v > 0 ? 'bal-pos' : ($v < 0 ? 'bal-neg' : 'bal-zero');

        $mbadge = function (?string $m): array {
            return match (strtolower($m ?? '')) {
                'mpesa', 'mobile_money' => ['M-Pesa', 'm-mpesa'],
                'bank' => ['Bank', 'm-bank'],
                'cash' => ['Cash', 'm-cash'],
                'credit' => ['Credit', 'm-credit'],
                'cheque' => ['Cheque', 'm-cheque'],
                default => [ucfirst($m ?? ''), ''],
            };
        };

        $rowCls = fn(array $e): string => match (true) {
            ($e['source_type'] ?? '') === 'waiver' => 'row-waiver',
            in_array($e['source_type'] ?? '', ['scholarship', 'discount', 'credit_transfer', 'credit', 'overpayment'])
                => 'row-credit',
            ($e['source_type'] ?? '') === 'payment' => 'row-pay',
            default => 'row-charge',
        };
    @endphp

    {{-- HEADER --}}
    <table class="hdr">
        <tr>
            <td class="logo" style="width:65%;">
                <img src="{{ public_path('assets/images/logos/tabor_logo.png') }}" alt="">
                <div class="title">Student Fee Statement</div>
                <div class="sub">+254 798 496129 &nbsp;|&nbsp; office@tabor.ac.ke &nbsp;|&nbsp; www.tabor.ac.ke</div>
                <div class="sub">Showbe Plaza, Pangani, Thika Highway, Nairobi</div>
            </td>
            <td style="width:35%; padding-left:16px;">
                <div class="ref-box">
                    <div class="lbl">Statement No.</div>
                    <div class="val">{{ $stmtRef }}</div>
                    <div class="lbl">Date Issued</div>
                    <div class="val">{{ now()->format('d M Y') }}</div>
                </div>
            </td>
        </tr>
    </table>

    {{-- META --}}
    <div class="section">Student &amp; Enrollment Details</div>
    <table class="meta">
        <tr>
            <td><span class="lbl">Student Name</span><span class="val">{{ $name ?: '-' }}</span></td>
            <td><span class="lbl">Student Number</span><span class="val">{{ $studNo }}</span></td>
        </tr>
        <tr>
            <td><span class="lbl">Course</span><span
                    class="val">{{ $course?->title ?? '-' }}{{ $course?->level ? ' - ' . $course->level : '' }}</span>
            </td>
            <td><span class="lbl">Trimester / Academic Year</span><span
                    class="val">{{ $trimester?->name ?? '-' }}{{ $academicYear ? ' / ' . $academicYear->name : '' }}</span>
            </td>
        </tr>
        <tr>
            <td><span class="lbl">Statement Period</span><span
                    class="val">{{ optional($statement['start_date'])->format('d M Y') ?? '-' }} to
                    {{ optional($statement['end_date'])->format('d M Y') ?? '-' }}</span></td>
            <td><span class="lbl">Progression</span><span class="val">Trimester
                    {{ $progression->trimester_sequence }}{{ $course?->number_of_trimesters ? ' of ' . $course->number_of_trimesters : '' }}
                    - {{ str_replace('_', ' ', ucfirst($progression->status)) }}</span></td>
        </tr>
    </table>

    {{-- LEDGER --}}
    <div class="section">Ledger</div>
    <table class="ledger">
        <thead>
            <tr>
                <th style="width:11%;">Date</th>
                <th style="width:14%;">Reference</th>
                <th>Description</th>
                <th style="width:12%;" class="r">DR (KES)</th>
                <th style="width:12%;" class="r">CR (KES)</th>
                <th style="width:13%;" class="r">Balance (KES)</th>
            </tr>
        </thead>
        <tbody>

            {{-- B/F --}}
            <tr class="row-bf">
                <td>{{ optional($statement['start_date'])->format('d M Y') ?? '-' }}</td>
                <td>B/F</td>
                <td>Balance Brought Forward</td>
                <td class="r"></td>
                <td class="r"></td>
                <td class="r {{ $bc($opening) }}">{{ number_format($opening, 2) }}</td>
            </tr>

            @forelse ($statement['ledger'] as $entry)
                @php
                    $dr = (float) ($entry['dr'] ?? 0);
                    $cr = (float) ($entry['cr'] ?? 0);
                    $bal = (float) ($entry['balance'] ?? 0);
                    $src = $entry['source_type'] ?? '';
                    $m = $entry['method'] ?? null;
                    [$mlabel, $mcls] = $mbadge($m);
                @endphp
                <tr class="{{ $rowCls($entry) }}">
                    <td>
                        {{ optional($entry['date'])->format('d M Y') ?? '-' }}
                        @if ($src === 'payment' && !empty($entry['actual_payment_date']))
                            @if (optional($entry['actual_payment_date'])->format('Ymd') !== optional($entry['date'])->format('Ymd'))
                                <br><span style="font-size:9px;color:#9ca3af;">Paid:
                                    {{ optional($entry['actual_payment_date'])->format('d M Y') }}</span>
                            @endif
                        @endif
                    </td>
                    <td>
                        {{ $entry['reference'] ?? '' }}
                        @if ($m && $src !== 'credit' && $mlabel)
                            &nbsp;<span class="badge {{ $mcls }}">{{ $mlabel }}</span>
                        @endif
                    </td>
                    <td>
                        {{ $entry['description'] ?? '' }}
                        @if (!empty($entry['sponsored_by']))
                            <br><span style="font-size:9.5px;color:#6b7280;">Sponsor:
                                {{ $entry['sponsored_by'] }}</span>
                        @endif
                        @if ($src === 'waiver' && !empty($entry['credit_reason']))
                            <br><span style="font-size:9.5px;">{{ $entry['credit_reason'] }}</span>
                        @endif
                    </td>
                    <td class="r">{{ $dr > 0 ? number_format($dr, 2) : '' }}</td>
                    <td class="r">{{ $cr > 0 ? number_format($cr, 2) : '' }}</td>
                    <td class="r {{ $bc($bal) }}">{{ number_format($bal, 2) }}</td>
                </tr>

                @if ($src === 'payment' && !empty($entry['allocations']))
                    @foreach ($entry['allocations'] as $alloc)
                        <tr class="row-alloc">
                            <td></td>
                            <td></td>
                            <td>Applied to: {{ $alloc['description'] ?? 'Fee Item' }}</td>
                            <td class="r"></td>
                            <td class="r">
                                {{ number_format((float) ($alloc['amount_allocated'] ?? ($alloc['amount'] ?? 0)), 2) }}
                            </td>
                            <td class="r"></td>
                        </tr>
                    @endforeach
                @endif

            @empty
                <tr>
                    <td colspan="6" class="c" style="color:#9ca3af;padding:14px;">No entries for this period.
                    </td>
                </tr>
            @endforelse

            {{-- Totals --}}
            <tr class="row-totals">
                <th colspan="3" style="text-align:right;">Total</th>
                <th class="r">{{ number_format($statement['total_debits'], 2) }}</th>
                <th class="r" style="color:#6ee7b7;">{{ number_format($statement['total_credits'], 2) }}</th>
                <th class="r {{ $tbc($closing) }}">{{ number_format($closing, 2) }}</th>
            </tr>

        </tbody>
    </table>

    {{-- OUTSTANDING / CLEARED --}}
    @if ($closing > 0)
        <div class="box-due">
            <div class="hd">Amount Outstanding</div>
            <div class="amt">KES {{ number_format($closing, 2) }}</div>
            <div class="pi">
                <span class="pk">M-Pesa Paybill:</span> No. <strong>4167821</strong> &nbsp; Account:
                <strong>{{ $student->admission_number ?? 'Your Adm No.' }}</strong><br>
                <span class="pk">Bank Transfer:</span> KCB Bank &nbsp; A/C: <strong>1332497152</strong> &nbsp;
                Branch: Kimathi Branch<br>
                <span class="pk">Enquiries:</span> office@tabor.ac.ke &nbsp; +254 798 496129
            </div>
        </div>
    @else
        <div class="box-ok">Account Cleared - No Outstanding Balance</div>
    @endif

    {{-- FOOTER --}}
    <table class="footer">
        <tr>
            <td>This is a computer-generated statement. No signature required.</td>
            <td style="text-align:right;">{{ $stmtRef }} &nbsp;|&nbsp; Generated:
                {{ now()->format('d M Y, h:i A') }}</td>
        </tr>
    </table>

</body>

</html>
