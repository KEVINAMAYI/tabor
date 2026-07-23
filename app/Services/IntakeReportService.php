<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Intake;
use App\Models\Lecturer;


class IntakeReportService
{
    public function getIntakes()
    {
        return Intake::all();
    }

}
