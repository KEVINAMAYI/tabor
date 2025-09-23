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

        .styled-payment-table th, .styled-payment-table td {
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


        @media print {
            body * {
                visibility: hidden !important;
            }

            .receipt-print, .receipt-print * {
                visibility: visible !important;
            }

            .receipt-print {
                position: absolute;
                top: 0;
                left: 0;
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
                        <th>Amount</th>
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
                            <td>${{ number_format($payment->amount, 2) }}</td>
                            <td>{{ ucfirst($payment->payment_method) }}</td>
                            <td>{{ \Carbon\Carbon::parse($payment->paid_at)->format('d M Y') }}</td>
                            <td>{{ $payment->reference }}</td>
                            <td>
                                <button class="btn-print" onclick="printReceipt(
                    '{{ $payment->enrollment->course->title ?? 'N/A' }}',
                    '{{ number_format($payment->amount, 2) }}',
                    '{{ ucfirst($payment->payment_method) }}',
                    '{{ \Carbon\Carbon::parse($payment->paid_at)->format('d M Y') }}',
                    '{{ $payment->reference }}'
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
                <div id="receipt" class="receipt-print">
                    <div
                        style="max-width: 600px; margin: auto; font-family: 'Segoe UI', sans-serif; padding: 40px; border: 1px solid #ddd; background-color: #fff;">

                        <!-- Logo -->
                        <div style="text-align: center; margin-bottom: 20px;">
                            <img src="assets/images/logos/tabor_logo.png" alt="Company Logo" style="height: 60px;">
                            <h4 style="margin-top: 10px; color: #0e334e;">Payment Receipt</h4>
                        </div>

                        <hr style="margin: 20px 0; border: none; border-top: 2px solid #f69121;">

                        <div style="margin-bottom: 20px;">
                            <p><strong>Course Title:</strong> <span id="receipt-course"></span></p>
                            <p><strong>Amount Paid:</strong> <span style="color: #0e334e;">KES <span id="receipt-amount"></span></span></p>
                            <p><strong>Payment Method:</strong> <span id="receipt-method"></span></p>
                            <p><strong>Date Paid:</strong> <span id="receipt-date"></span></p>
                            <p><strong>Reference:</strong> <span id="receipt-reference"></span></p>
                        </div>

                        <hr style="margin: 20px 0; border: none; border-top: 2px dashed #ccc;">

                        <p style="text-align: center; font-size: 13px; color: #888; margin-top: 30px;">
                            Thank you for your payment. This receipt serves as proof of payment.
                        </p>

                        <p style="text-align: center; font-size: 12px; color: #bbb; margin-top: 10px;">
                            &copy; {{ date('Y') }} Tabor
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
    <script>
        function printReceipt(course, amount, method, date, reference) {

            // Fill receipt
            document.getElementById('receipt-course').innerText = course;
            document.getElementById('receipt-amount').innerText = amount;
            document.getElementById('receipt-method').innerText = method;
            document.getElementById('receipt-date').innerText = date;
            document.getElementById('receipt-reference').innerText = reference;

            // Delay print to allow DOM to update
            setTimeout(() => {
                window.print();
            }, 500); // 300ms is usually enough
        }
    </script>
@endpush





