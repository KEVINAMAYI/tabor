<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\EnrollmentProgression;
use App\Models\FeeDefinition;
use App\Models\Student;
use App\Models\StudentFeeItem;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportHistoricalFeeItems extends Command
{
    protected $signature = 'finance:import-historical-fees
        {file : Path to xlsx/csv file}
        {--yes : Run without confirmation}
        {--dry-run : Validate only}';

    protected $description = 'Import historical progression charges and discounts';

    public function handle(): int
    {
        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return self::FAILURE;
        }

        if (!$this->option('dry-run') && !$this->option('yes')) {
            if (!$this->confirm('Import historical fee items?')) {
                return self::SUCCESS;
            }
        }

        $rows = $this->readRows($file);

        $created = 0;
        $updated = 0;
        $skipped = 0;

        DB::beginTransaction();

        try {
            foreach ($rows as $index => $row) {
                $line = $index + 2;

                if (blank($row['admission_number'] ?? null)) {
                    continue;
                }

                $student = Student::where('admission_number', trim($row['admission_number']))->first();
                $course = Course::where('code', trim($row['course_code']))->first();

                if (!$student || !$course) {
                    throw new \RuntimeException("Line {$line}: Student or course not found.");
                }

                $enrollment = Enrollment::where('student_id', $student->id)
                    ->where('course_id', $course->id)
                    ->whereIn('status', ['active', 'course_completed', 'pending_graduation', 'graduated'])
                    ->latest('id')
                    ->first();

                if (!$enrollment) {
                    throw new \RuntimeException("Line {$line}: Billable enrollment not found.");
                }

                $progression = EnrollmentProgression::where('enrollment_id', $enrollment->id)
                    ->where('trimester_sequence', (int) $row['trimester_sequence'])
                    ->first();

                if (!$progression) {
                    throw new \RuntimeException("Line {$line}: Progression not found.");
                }

                $amount = (float) $row['amount'];

                if ($amount == 0.0) {
                    $skipped++;
                    continue;
                }

                $feeDefinition = FeeDefinition::firstOrCreate(
                    ['name' => trim($row['fee_name'])],
                    [
                        'scope' => $amount < 0 ? 'enrollment' : 'course',
                        'applies_once' => false,
                        'active' => true,
                        'default_amount' => 0,
                    ]
                );

                $externalRef = trim($row['external_ref'] ?? '');
                $mode = trim($row['mode'] ?? 'upsert');

                $payload = [
                    'student_id' => $student->id,
                    'enrollment_id' => $enrollment->id,
                    'enrollment_progression_id' => $progression->id,
                    'course_fee_plan_id' => null,
                    'trimester_id' => $progression->trimester_id,
                    'fee_definition_id' => $feeDefinition->id,
                    'description' => trim($row['description'] ?: $row['fee_name']),
                    'amount' => $amount,
                    'amount_paid' => 0,
                    'balance' => $amount,
                    'charge_date' => Carbon::parse($row['charge_date'])->toDateString(),
                    'due_date' => null,
                    'status' => $amount < 0 ? 'paid' : 'pending',
                    'notes' => trim(($row['notes'] ?? '') . ' HIST_REF:' . $externalRef),
                ];

                $existing = StudentFeeItem::query()
                    ->where('student_id', $student->id)
                    ->where('enrollment_id', $enrollment->id)
                    ->where('enrollment_progression_id', $progression->id)
                    ->where('notes', 'like', '%HIST_REF:' . $externalRef . '%')
                    ->first();

                if ($existing && $mode === 'skip_if_exists') {
                    $skipped++;
                    continue;
                }

                if ($this->option('dry-run')) {
                    $existing ? $updated++ : $created++;
                    continue;
                }

                if ($existing) {
                    if ($existing->amount_paid > 0 || $existing->allocations()->exists()) {
                        throw new \RuntimeException("Line {$line}: Existing item has allocations; cannot update.");
                    }

                    $existing->update($payload);
                    $updated++;
                } else {
                    StudentFeeItem::create($payload);
                    $created++;
                }
            }

            if ($this->option('dry-run')) {
                DB::rollBack();
                $this->info("DRY RUN OK. Would create {$created}, update {$updated}, skip {$skipped}.");
                return self::SUCCESS;
            }

            DB::commit();

            $this->info("Import complete. Created {$created}, updated {$updated}, skipped {$skipped}.");

            return self::SUCCESS;
        } catch (\Throwable $th) {
            DB::rollBack();

            Log::error('Historical fee import failed', [
                'message' => $th->getMessage(),
            ]);

            $this->error($th->getMessage());

            return self::FAILURE;
        }
    }

    protected function readRows(string $file): array
    {
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getSheetByName('Historical_Fee_Items')
            ?? $spreadsheet->getActiveSheet();

        $rows = $sheet->toArray(null, true, true, true);

        $headers = array_map(
            fn($value) => trim((string) $value),
            array_shift($rows)
        );

        $data = [];

        foreach ($rows as $row) {
            $mapped = [];

            foreach ($headers as $column => $header) {
                $mapped[$header] = $row[$column] ?? null;
            }

            if (collect($mapped)->filter()->isNotEmpty()) {
                $data[] = $mapped;
            }
        }

        return $data;
    }
}
