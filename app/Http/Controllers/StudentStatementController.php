<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Trimester;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use App\Services\StudentStatementService;
use Barryvdh\DomPDF\Facade\Pdf;

class StudentStatementController extends Controller
{
    public function show(Request $request, Student $student)
    {
        $request->validate([
            'trimester_id' => ['required', 'exists:trimesters,id'],
            'enrollment_id' => ['nullable', 'exists:enrollments,id'],
        ]);

        $trimester = Trimester::with('academicYear')->findOrFail($request->integer('trimester_id'));
        $enrollmentId = $request->filled('enrollment_id') ? (int) $request->enrollment_id : null;

        if ($enrollmentId) {
            Enrollment::query()
                ->where('student_id', $student->id)
                ->findOrFail($enrollmentId);
        }

        $statement = app(StudentStatementService::class)->buildTrimesterStatement(
            student: $student,
            trimester: $trimester,
            enrollmentId: $enrollmentId
        );

        $pdf = Pdf::loadView('statements.student-statement', [
            'statement' => $statement,
        ]);

        return $pdf->stream('student-statement.pdf');
        /* return view('statements.student-statement', [
            'statement' => $statement,
        ]); */
    }
}