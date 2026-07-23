{{-- <!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Payment Receipt</title>

    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            color: #111827;
            margin: 30px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header img {
            height: 90px;
        }

        .title {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        td,
        th {
            border: 1px solid #d1d5db;
            padding: 7px;
        }

        th {
            background: #f3f4f6;
            text-align: left;
        }

        .text-end {
            text-align: right;
        }

        .amount {
            font-size: 18px;
            font-weight: bold;
            color: #15803d;
        }

        .muted {
            color: #6b7280;
        }

        .footer {
            margin-top: 35px;
            font-size: 11px;
            color: #6b7280;
        }
    </style>
</head>

<body>
    @php
        $student = $payment->enrollment?->student;
        $course = $payment->enrollment?->course;
        $allocated = $payment->allocations->sum('amount_allocated');
        $unallocated = $payment->unallocated_balance ?? (float) $payment->amount - (float) $allocated;
    @endphp

    <div class="header">
        <img src="{{ public_path('assets/images/logos/tabor_logo.png') }}" alt="Logo">
        <div class="title">Official Payment Receipt</div>
        <div class="muted">Tabor Training Institute</div>
    </div>

    <table>
        <tr>
            <td><strong>Receipt No:</strong> {{ $payment->receipt_no ?? 'PAY-' . $payment->id }}</td>
            <td><strong>Date:</strong> {{ optional($payment->payment_date ?? $payment->paid_at)->format('d M Y') }}</td>
        </tr>
        <tr>
            <td><strong>Reference:</strong>
                {{ $payment->transaction_id ?: $payment->reference ?: $payment->receipt_no ?: 'PAY-' . $payment->id }}
            </td>
            <td><strong>Method:</strong> {{ ucfirst($payment->method ?? ($payment->payment_method ?? 'N/A')) }}</td>
        </tr>
        <tr>
            <td><strong>Student:</strong>
                {{ $student ? trim($student->first_name . ' ' . $student->last_name) : 'Multiple / Unmapped' }}</td>
            <td><strong>Admission No:</strong> {{ $student?->admission_number ?? '—' }}</td>
        </tr>
        <tr>
            <td colspan="2"><strong>Course:</strong> {{ $course?->title ?? 'Multiple / Unmapped' }}</td>
        </tr>
        <tr>
            <td colspan="2"><strong>Payer/Narration:</strong> {{ $payment->payer ?? '—' }}</td>
        </tr>
        <tr>
            <td colspan="2" class="amount">
                Amount Received: KES {{ number_format($payment->amount, 2) }}
            </td>
        </tr>
    </table>

    <h4>Allocation Breakdown</h4>

    <table>
        <thead>
            <tr>
                <th>Fee Item</th>
                <th>Course</th>
                <th class="text-end">Allocated</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payment->allocations as $allocation)
                <tr>
                    <td>{{ $allocation->studentFeeItem?->description ?? 'Fee Item' }}</td>
                    <td>{{ $allocation->studentFeeItem?->enrollment?->course?->title ?? '—' }}</td>
                    <td class="text-end">{{ number_format($allocation->amount_allocated, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="muted">No allocation recorded yet.</td>
                </tr>
            @endforelse

            <tr>
                <th colspan="2">Allocated Total</th>
                <th class="text-end">{{ number_format($allocated, 2) }}</th>
            </tr>

            <tr>
                <th colspan="2">Unallocated Balance</th>
                <th class="text-end">{{ number_format($unallocated, 2) }}</th>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        Printed on {{ now()->format('d M Y h:i A') }}<br>
        This receipt acknowledges money received only. Allocation may be updated by the finance office where applicable.
    </div>
</body>

</html>
 --}}



<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Payment Receipt</title>

    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 13px;
            color: #111827;
            margin: 30px;
            background: #fff;
        }

        .receipt {
            max-width: 650px;
            margin: auto;
            padding: 25px;
            border: 1px solid #bbb;
            border-radius: 6px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header img {
            height: 70px;
        }

        .header h2 {
            margin: 10px 0 4px;
            color: #0e334e;
            font-size: 20px;
        }

        .header p {
            font-size: 13px;
            color: #666;
            margin: 0;
        }

        hr.main {
            border: none;
            border-top: 2px solid #0e334e;
            margin: 15px 0 25px;
        }

        hr.dashed {
            border: none;
            border-top: 1px dashed #ccc;
            margin: 25px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table {
            margin-bottom: 15px;
            font-size: 13px;
        }

        .info-table td {
            padding: 4px 0;
        }

        .section {
            border: 1px solid #ddd;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .section-header {
            background: #f8f9fb;
            padding: 10px 15px;
            border-bottom: 1px solid #ddd;
        }

        .section-header h3 {
            color: #0e334e;
            margin: 0;
            font-size: 15px;
        }

        .section-table td {
            padding: 8px 15px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        .section-table tr:last-child td {
            border-bottom: none;
        }

        .amount {
            color: #0e334e;
            font-weight: bold;
            font-size: 16px;
        }

        .footer {
            text-align: center;
            font-size: 13px;
            color: #555;
        }

        .footer small {
            color: #888;
        }
    </style>
</head>

<body>
    @php
        $student = $payment->enrollment?->student;
        $course = $payment->enrollment?->course;

        $studentName = $student
            ? trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''))
            : 'Multiple / Unmapped';

        $reference =
            $payment->transaction_id ?:
            $payment->reference ?:
            $payment->receipt_no ?:
            $payment->payer ?:
            'PAY-' . $payment->id;

        $receiptNo = $payment->receipt_no ?: 'PAY-' . $payment->id;

        $paymentDate = $payment->payment_date ?? $payment->paid_at;
    @endphp

    <div class="receipt">
        <div class="header">
            <img src="{{ public_path('assets/images/logos/tabor_logo.png') }}" alt="Tabor Logo">

            <h2>Tabor Training Institute</h2>

            <p>Official Payment Receipt</p>
        </div>

        <hr class="main">

        {{-- <table class="info-table">
            <tr>
                <td style="text-align:right;">
                    <strong>{{ $receiptNo }}</strong>
                </td>
            </tr>

            <tr>
                <td style="color:#555;">Date:</td>
                <td style="text-align:right;">
                    <strong>{{ optional($paymentDate)->format('d M Y') }}</strong>
                </td>
            </tr>
        </table> --}}

        <div class="section">
            <div class="section-header">
                <h3>Student Information</h3>
            </div>

            <table class="section-table">
                <tr>
                    <td><strong>Student Name:</strong></td>
                    <td>{{ $studentName }}</td>
                </tr>

                <tr>
                    <td><strong>Admission No:</strong></td>
                    <td>{{ 'TTI/' . $student?->admission_number .'/'.$course?->code.'/'. $student->created_at->year }}</td>
                </tr>

                <tr>
                    <td><strong>Course Title:</strong></td>
                    <td>{{ $course?->title }} - {{ $course?->level }} </td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-header">
                <h3>Payment Details</h3>
            </div>

            <table class="section-table">
                <tr>
                    <td><strong>Payment Date:</strong></td>
                    <td>
                        {{ $paymentDate->format('d M Y') }}
                    </td>
                </tr>
                <tr>
                    <td><strong>Amount Paid:</strong></td>
                    <td class="amount">
                        KES {{ number_format($payment->amount, 2) }}
                    </td>
                </tr>

                <tr>
                    <td><strong>Payment Method:</strong></td>
                    <td>{{ ucfirst($payment->method ?? ($payment->payment_method ?? 'N/A')) }}</td>
                </tr>

                <tr>
                    <td><strong>Reference:</strong></td>
                    <td>{{ $reference }}</td>
                </tr>

                <tr>
                    <td><strong>Payment For:</strong></td>
                    <td>{{ ucfirst($payment->payment_reason ?? 'N/A') }}</td>
                </tr>

                <tr>
                    <td><strong>Payer / Narration:</strong></td>
                    <td>{{ $payment->payer ?? '—' }}</td>
                </tr>
            </table>
        </div>

        <hr class="dashed">

        <div class="footer">
            <p>
                Thank you for your payment. This receipt serves as
                <strong>official proof of payment</strong>.
            </p>

            <small>
                For assistance, contact office@tabor.ac.ke
            </small>

            <p style="font-size:12px;color:#aaa;margin-top:10px;">
                &copy; {{ date('Y') }} Tabor Training Institute. All Rights Reserved.
            </p>
        </div>
    </div>
</body>

</html>
