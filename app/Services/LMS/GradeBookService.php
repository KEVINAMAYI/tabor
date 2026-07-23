<?php

namespace App\Services\LMS;

use App\Models\Assessment;
use App\Models\AssessmentSubmission;
use App\Models\Enrollment;
use App\Models\GradeWeightSetting;
use App\Models\IntakeModule;
use Illuminate\Support\Collection;

class GradeBookService
{
    // Grade thresholds (Kenya standard)
    const GRADE_SCALE = [
        ['min' => 70, 'letter' => 'A', 'label' => 'Distinction'],
        ['min' => 60, 'letter' => 'B', 'label' => 'Credit'],
        ['min' => 50, 'letter' => 'C', 'label' => 'Pass'],
        ['min' => 40, 'letter' => 'D', 'label' => 'Supplementary'],
        ['min' =>  0, 'letter' => 'E', 'label' => 'Fail'],
    ];

    /**
     * Compute a student's grade for one intake module.
     *
     * Returns:
     *   cat_score        – weighted CAT contribution (out of 30)
     *   exam_score       – weighted Exam contribution (out of 70)
     *   final_score      – total out of 100
     *   grade_letter     – A / B / C / D / E
     *   grade_label      – Distinction / Credit / Pass / Supplementary / Fail
     *   cat_submissions  – individual CAT results
     *   exam_submission  – exam result or null
     *   has_grade        – false when no submissions exist yet
     */
    public function computeForEnrollment(Enrollment $enrollment, IntakeModule $intakeModule): array
    {
        $weights = GradeWeightSetting::active()->get()->keyBy('assessment_type');

        $catWeight  = (float) ($weights->get('CAT')?->weight_percentage  ?? 30);
        $examWeight = (float) ($weights->get('Exam')?->weight_percentage ?? 70);

        // All graded submissions for this student in this module
        $submissions = AssessmentSubmission::query()
            ->where('enrollment_id', $enrollment->id)
            ->where('status', 'graded')
            ->whereHas('assessment', fn($q) => $q->where('intake_module_id', $intakeModule->id))
            ->with('assessment')
            ->get();

        $catSubmissions  = $submissions->filter(fn($s) => $s->assessment->type === Assessment::TYPE_CAT);
        $examSubmission  = $submissions->first(fn($s)  => $s->assessment->type === Assessment::TYPE_EXAM);

        if ($submissions->isEmpty()) {
            return $this->emptyGrade($intakeModule);
        }

        // CAT contribution: average CAT percentage × weight
        $catScore = 0.0;
        if ($catSubmissions->isNotEmpty()) {
            $catPercentages = $catSubmissions->map(function ($s) {
                $max = (int) ($s->assessment->max_marks ?: 100);
                return ((float) $s->mark / $max) * 100;
            });
            $catScore = ($catPercentages->avg() / 100) * $catWeight;
        }

        // Exam contribution
        $examScore = 0.0;
        if ($examSubmission) {
            $max = (int) ($examSubmission->assessment->max_marks ?: 100);
            $examScore = (((float) $examSubmission->mark) / $max) * $examWeight;
        }

        $finalScore = round($catScore + $examScore, 2);
        [$letter, $label] = $this->letterGrade($finalScore);

        return [
            'has_grade'       => true,
            'cat_score'       => round($catScore, 2),
            'exam_score'      => round($examScore, 2),
            'final_score'     => $finalScore,
            'grade_letter'    => $letter,
            'grade_label'     => $label,
            'cat_weight'      => $catWeight,
            'exam_weight'     => $examWeight,
            'cat_submissions' => $catSubmissions->values(),
            'exam_submission' => $examSubmission,
            'module'          => $intakeModule->module,
        ];
    }

    /**
     * Compute grades for all modules a student is enrolled in.
     */
    public function computeAllForEnrollment(Enrollment $enrollment): Collection
    {
        $intakeModules = IntakeModule::whereIn('trimester_id', $enrollment->resolvedTrimesterIds())
            ->whereHas('module', fn($q) => $q->where('course_id', $enrollment->course_id))
            ->with(['module', 'assessments'])
            ->get();

        return $intakeModules->map(fn($im) => $this->computeForEnrollment($enrollment, $im));
    }

    /**
     * Get all students' grades for a single intake module (for lecturer grade view).
     */
    public function computeForModule(IntakeModule $intakeModule): Collection
    {
        $enrollments = Enrollment::forTrimester($intakeModule->trimester_id)
            ->whereHas('course.modules', fn($q) => $q->where('modules.id', $intakeModule->module_id))
            ->with('student.user')
            ->get();

        return $enrollments->map(fn($e) => [
            'enrollment' => $e,
            'student'    => $e->student,
            ...$this->computeForEnrollment($e, $intakeModule),
        ]);
    }

    private function letterGrade(float $score): array
    {
        foreach (self::GRADE_SCALE as $tier) {
            if ($score >= $tier['min']) {
                return [$tier['letter'], $tier['label']];
            }
        }
        return ['E', 'Fail'];
    }

    private function emptyGrade(IntakeModule $intakeModule): array
    {
        return [
            'has_grade'       => false,
            'cat_score'       => 0,
            'exam_score'      => 0,
            'final_score'     => 0,
            'grade_letter'    => '-',
            'grade_label'     => 'No Results Yet',
            'cat_weight'      => 30,
            'exam_weight'     => 70,
            'cat_submissions' => collect(),
            'exam_submission' => null,
            'module'          => $intakeModule->module,
        ];
    }
}
