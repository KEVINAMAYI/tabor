<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Lecturer;


class CourseReportService
{
    public function getCourses()
    {
        return Course::all();
    }

}
