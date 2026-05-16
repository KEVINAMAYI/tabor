<?php

namespace App\Console\Commands;

use App\Models\StudentFeeItem;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\File;

class ExportHistoricalFeeItems extends Command
{
    protected $signature = 'finance:export-historical-fees
        {file=storage/app/imports/historical_fee_items_export.xlsx}';

    protected $description = 'Export all student fee items for historical correction';

    public function handle(): int
    {
        $file = base_path($this->argument('file'));

        File::ensureDirectoryExists(dirname($file));

        $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Historical_Fee_Items');

        $headers = [
            'student_id',
            'admission_number',
            'student_name',
            'course_code',
            'course_name',
            'enrollment_id',
            'progression_id',
            'trimester_sequence',
            'trimester_name',
            'progression_status',
            'fee_definition_id',
            'fee_name',
            'description',
            'amount',
            'amount_paid',
            'balance',
            'charge_date',
            'status',
            'notes',
        ];

        $sheet->fromArray($headers, null, 'A1');

        $items = StudentFeeItem::query()
            ->with([
                'student',
                'enrollment.course',
                'progression.trimester.academicYear',
                'trimester.academicYear',
                'feeDefinition',
            ])
            ->leftJoin('fee_definitions', 'fee_definitions.id', '=', 'student_fee_items.fee_definition_id')
            ->leftJoin('students', 'students.id', '=', 'student_fee_items.student_id')
            ->leftJoin('enrollments', 'enrollments.id', '=', 'student_fee_items.enrollment_id')
            ->leftJoin('courses', 'courses.id', '=', 'enrollments.course_id')
            ->leftJoin('enrollment_progressions', 'enrollment_progressions.id', '=', 'student_fee_items.enrollment_progression_id')
            ->select('student_fee_items.*')
            ->orderBy('students.admission_number')
            ->orderBy('enrollments.id')
            ->orderByRaw("
                CASE
                    WHEN fee_definitions.scope = 'student'
                     AND fee_definitions.applies_once = 1
                    THEN 0
                    ELSE 1
                END
            ")
            ->orderBy('enrollment_progressions.trimester_sequence')
            ->orderBy('student_fee_items.charge_date')
            ->orderBy('student_fee_items.id')
            ->get();

        $row = 2;

        foreach ($items as $item) {
            $student = $item->student;
            $enrollment = $item->enrollment;
            $course = $enrollment?->course;
            $progression = $item->progression;
            $trimester = $progression?->trimester ?? $item->trimester;
            $feeDefinition = $item->feeDefinition;

            $sheet->fromArray([
                $student?->id,
                $student?->admission_number,
                trim(($student?->first_name ?? '') . ' ' . ($student?->last_name ?? '')),
                $course?->code,
                $course?->title,
                $enrollment?->id,
                $progression?->id,
                $progression?->trimester_sequence,
                trim(($trimester?->name ?? '') . ' ' . ($trimester?->academicYear?->name ?? '')),
                $progression?->status,
                $feeDefinition?->id,
                $feeDefinition?->name ?? $item->description,
                $item->description,
                $item->amount,
                $item->amount_paid,
                $item->balance,
                optional($item->charge_date)->format('Y-m-d'),
                $item->status,
                $item->notes,
            ], null, 'A' . $row);

            $row++;
        }

        foreach (range('A', 'V') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        (new Xlsx($spreadsheet))->save($file);

        $this->info("Exported {$items->count()} fee item(s) to: {$file}");

        return self::SUCCESS;
    }
}
