<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class PaymentReceiptController extends Controller
{
    public function show(Payment $payment)
    {
        $payment->load([
            'enrollment.student',
            'enrollment.course',
            'allocations.studentFeeItem.enrollment.course',
            'allocations.studentFeeItem.feeDefinition',
        ]);

        $pdf = Pdf::loadView('receipts.payment-receipt', [
            'payment' => $payment,
        ]);

        return $pdf->stream('payment-receipt-' . Str::random(8) . '.pdf');
    }
}
