<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Lecturer;
use App\Models\Payment;


class PaymentsReportService
{
    public function getPayments()
    {
        return Payment::with(['enrollment.student', 'enrollment.course'])->latest()->get();
    }

}
