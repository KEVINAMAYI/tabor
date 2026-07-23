<?php

use Livewire\Volt\Component;

new class extends Component {
    public $payments;

    public function mount()
    {
        $this->loadPayments();
    }

    public function loadPayments()
    {
        $student = auth()->user()->student;

        if (!$student) {
            $this->payments = collect(); // empty collection if no student linked
            return;
        }

        $this->payments = $student->payments()->with('enrollment.course')->latest()->get();
    }
}; ?>

@push('styles')
    <style>
        .styled-payment-table {
            width: 100%;
            border-collapse: collapse;
            font-family: 'Segoe UI', sans-serif;
            font-size: 14px;
            color: #0e334e;
            background-color: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
        }

        .styled-payment-table thead {
            background-color: #f69121;
            color: #fff;
        }

        .styled-payment-table thead th {
            padding: 14px 18px;
            text-align: left;
            font-weight: 600;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #f5f5f5;
        }

        .styled-payment-table tbody td {
            padding: 14px 18px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }

        .styled-payment-table tbody tr:nth-child(even) {
            background-color: #fafafa;
        }

        .styled-payment-table tbody tr:hover {
            background-color: #fff7ef;
        }

        .styled-payment-table .btn-view {
            background-color: #0e334e;
            color: #fff;
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            font-size: 13px;
            transition: background-color 0.3s ease;
        }

        .styled-payment-table .btn-view:hover {
            background-color: #f69121;
            color: #fff;
        }

        .styled-payment-table .text-center {
            text-align: center;
            padding: 20px;
            color: #888;
        }

        /* Reuse previous styles */
        .styled-payment-table {
            width: 100%;
            border-collapse: collapse;
            font-family: 'Segoe UI', sans-serif;
            font-size: 14px;
            color: #0e334e;
            background-color: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            margin-top: 20px;
        }

        .styled-payment-table thead {
            background-color: #f69121;
            color: #fff;
        }

        .styled-payment-table th,
        .styled-payment-table td {
            padding: 14px 18px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }

        .styled-payment-table tr:nth-child(even) {
            background-color: #fafafa;
        }

        .styled-payment-table tr:hover {
            background-color: #fff7ef;
        }

        .btn-print {
            background-color: #0e334e;
            color: #fff;
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            font-size: 13px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-print:hover {
            background-color: #f69121;
            color: #fff;
        }

        /* Hidden printable receipt */
        .receipt-print {
            visibility: hidden;
            position: absolute;
            top: -9999px;
            left: -9999px;
        }

        /* Add top border to the last row */
        .styled-payment-table tbody tr:last-child td {
            border-top: 1px solid #f0f0f0;
        }


        @media print {
            @page {
                size: auto;
                margin: 0; /* removes default page margin */
            }

            html,
            body {
                margin: 0 !important;
                padding: 0 !important;
                height: auto !important;
            }

            body * {
                visibility: hidden !important;
            }

            .receipt-print,
            .receipt-print * {
                visibility: visible !important;
            }

            .receipt-print {
                position: absolute !important;
                top: 0 !important;
                left: 0 !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0mm !important; /* acts as page margin */
                background: white !important;
                box-sizing: border-box !important;
                page-break-inside: avoid; /* prevents breaking receipt across pages */
                page-break-after: auto !important; /* prevents extra blank page */
            }
        }

    </style>
@endpush
<div class="row">
    <div class="col-12">
        <div class="card card-body">
            <h4 class="mb-4">My Payments</h4>

            <div class="table-responsive">
                <!-- Payment Table -->
                <table class="styled-payment-table">
                    <thead>
                    <tr>
                        <th>Course Title</th>
                        <th>Amount (KSH)</th>
                        <th>Method</th>
                        <th>Paid On</th>
                        <th>Reference</th>
                        <th>Receipt</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td>{{ $payment->enrollment->course->title ?? 'N/A' }}</td>
                            <td>{{ number_format($payment->amount, 2) }}</td>
                            <td>{{ ucfirst($payment->payment_method) }}</td>
                            <td>{{ \Carbon\Carbon::parse($payment->paid_at)->format('d M Y') }}</td>
                            <td>{{ $payment->reference }}</td>
                            <td>
                                <button class="btn-print"
                                        onclick="printReceipt(
                    '{{ $payment->enrollment->course->title ?? 'N/A' }}',
                    '{{ number_format($payment->amount, 2) }}',
                    '{{ ucfirst($payment->payment_method) }}',
                    '{{ \Carbon\Carbon::parse($payment->paid_at)->format('d M Y') }}',
                    '{{ $payment->reference }}',
                    '{{ $payment->enrollment->student->first_name ?? 'N/A' }}',
                    '{{ $payment->enrollment->student->last_name ?? 'N/A' }}',
                    '{{ 'RCT' . $payment->id }}',
                    '{{ $payment->enrollment->course->level ?? 'N/A' }}',
                    '{{ ucfirst($payment->payment_reason) ?? 'N/A' }}',
                )">
                                    <i class="ti ti-printer"></i> Print
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No payments found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>

                <!-- Hidden Printable Receipt -->
                <!-- Hidden Printable Receipt -->
                <div id="receipt" class="receipt-print">
                    <div
                        style="max-width: 650px; margin: auto; font-family: 'Segoe UI', Tahoma, sans-serif; padding: 25px; border: 1px solid #bbb; background-color: #fff; border-radius: 6px; box-shadow: 0 2px 6px rgba(0,0,0,0.05);">

                        <!-- Header -->
                        <div style="text-align: center; margin-bottom: 20px;">
                            <img src="assets/images/logos/tabor_logo.png" alt="Company Logo" style="height: 70px;">
                            <h2 style="margin: 10px 0 4px; color: #0e334e; letter-spacing: 0.4px; font-size: 20px;">Tabor Training Institute</h2>
                            <p style="font-size: 13px; color: #666; margin: 0;">Official Payment Receipt</p>
                        </div>

                        <hr style="border: none; border-top: 2px solid #0e334e; margin: 15px 0 25px;">

                        <!-- Receipt Info -->
                        <table style="width: 100%; font-size: 13px; border-collapse: collapse; margin-bottom: 15px;">
                            <tr>
                                <td style="color: #555;">Receipt No:</td>
                                <td style="text-align: right;"><strong><span id="receipt-number"></span></strong></td>
                            </tr>
                            <tr>
                                <td style="color: #555;">Date:</td>
                                <td style="text-align: right;"><strong><span id="receipt-date"></span></strong></td>
                            </tr>
                        </table>

                        <!-- Section: Student Info -->
                        <div style="border: 1px solid #ddd; border-radius: 6px; margin-bottom: 20px;">
                            <div style="background: #f8f9fb; padding: 10px 15px; border-bottom: 1px solid #ddd;">
                                <h3 style="color: #0e334e; margin: 0; font-size: 15px;">Student Information</h3>
                            </div>
                            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                                <tr>
                                    <td style="padding: 8px 15px; border-bottom: 1px solid #eee;"><strong>First Name:</strong></td>
                                    <td style="padding: 8px 15px; border-bottom: 1px solid #eee;"><span id="receipt-first-name"></span></td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 15px; border-bottom: 1px solid #eee;"><strong>Last Name:</strong></td>
                                    <td style="padding: 8px 15px; border-bottom: 1px solid #eee;"><span id="receipt-last-name"></span></td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 15px; border-bottom: 1px solid #eee;"><strong>Course Title:</strong></td>
                                    <td style="padding: 8px 15px; border-bottom: 1px solid #eee;"><span id="receipt-course"></span></td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 15px; border-bottom: 1px solid #eee;"><strong>Course Level:</strong></td>
                                    <td style="padding: 8px 15px; border-bottom: 1px solid #eee;"><span id="receipt-course-level"></span></td>
                                </tr>
                            </table>
                        </div>

                        <!-- Section: Payment Info -->
                        <div style="border: 1px solid #ddd; border-radius: 6px;">
                            <div style="background: #f8f9fb; padding: 10px 15px; border-bottom: 1px solid #ddd;">
                                <h3 style="color: #0e334e; margin: 0; font-size: 15px;">Payment Details</h3>
                            </div>
                            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                                <tr>
                                    <td style="padding: 8px 15px; border-bottom: 1px solid #eee;"><strong>Amount Paid:</strong></td>
                                    <td style="padding: 8px 15px; border-bottom: 1px solid #eee; color: #0e334e; font-weight: bold;">KES <span id="receipt-amount"></span></td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 15px; border-bottom: 1px solid #eee;"><strong>Payment Method:</strong></td>
                                    <td style="padding: 8px 15px; border-bottom: 1px solid #eee;"><span id="receipt-method"></span></td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 15px; border-bottom: 1px solid #eee;"><strong>Reference:</strong></td>
                                    <td style="padding: 8px 15px; border-bottom: 1px solid #eee;"><span id="receipt-reference"></span></td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 15px; border-bottom: 1px solid #eee;"><strong>Reason:</strong></td>
                                    <td style="padding: 8px 15px; border-bottom: 1px solid #eee;"><span id="receipt-payment-reason"></span></td>
                                </tr>
                            </table>
                        </div>

                        <hr style="border: none; border-top: 1px dashed #ccc; margin: 25px 0;">

                        <!-- Footer -->
                        <div style="text-align: center; font-size: 13px; color: #555;">
                            <p>Thank you for your payment. This receipt serves as <strong>official proof of payment</strong>.</p>
                            <p style="font-size: 12px; color: #888; margin-top: 8px;">For assistance, contact
                                <a href="mailto:support@tabor.ac.ke" style="color: #0e334e; text-decoration: none;">office@tabor.ac.ke</a>
                            </p>
                            <p style="font-size: 12px; color: #aaa; margin-top: 10px;">&copy; {{ date('Y') }} Tabor Training Institute All Rights Reserved.</p>
                        </div>
                    </div>
                </div>


            </div>
        </div>
    </div>
</div>
@push('scripts')
    <script>
        function printReceipt(course, amount, method, date, reference, firstName, lastName, receiptNumber, level, reason) {
            // Fill receipt
            document.getElementById('receipt-course').innerText = course;
            document.getElementById('receipt-amount').innerText = amount;
            document.getElementById('receipt-method').innerText = method;
            document.getElementById('receipt-date').innerText = date;
            document.getElementById('receipt-reference').innerText = reference;
            document.getElementById('receipt-first-name').innerText = firstName;
            document.getElementById('receipt-last-name').innerText = lastName;
            document.getElementById('receipt-number').innerText = receiptNumber;
            document.getElementById('receipt-course-level').innerText = level;
            document.getElementById('receipt-payment-reason').innerText = reason;

            // Delay print to allow DOM to update
            setTimeout(() => {
                window.print();
            }, 500); // 300ms is usually enough
        }
    </script>
@endpush
