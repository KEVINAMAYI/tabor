<?php

namespace App\Services;

use App\Models\Lecturer;


class LecturerReportService
{
    public function getLecturers()
    {
        return Lecturer::with('user')->latest()->get();
    }

}
