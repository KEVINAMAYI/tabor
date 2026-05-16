<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\EnrollmentProgression;
use App\Services\StudentStatementService;
use Barryvdh\DomPDF\Facade\Pdf;

class StudentStatementController extends Controller
{
    public function show(Student $student, EnrollmentProgression $progression)
    {
        if ((int) $progression->student_id !== (int) $student->id) {
            abort(404);
        }

        $statement = app(StudentStatementService::class)
            ->buildProgressionStatement($student, $progression);

        $pdf = Pdf::loadView('statements.student-statement', [
            'statement' => $statement,
        ]);

        return $pdf->stream(trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? '')) . '_' . $statement['course']->code ?? $statement['course']->title . '_Trimester ' . $progression->trimester_sequence . '.pdf');
    }
}
