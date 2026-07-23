<?php

namespace App\Services;

use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\Lecturer;


class ClassGroupsReportService
{
    public function getClassGroups()
    {
        return ClassGroup::with('intake')->latest()->get();
    }

}
