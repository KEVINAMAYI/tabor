<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // MySQL requires rebuilding the column to expand an enum. This raw
        // statement is MySQL-only syntax; guarded so it doesn't error on other
        // drivers. Known gap: on sqlite (used by the test suite), the CHECK
        // constraint from create_course_fee_plans_table's original enum() stays
        // at its narrower original value list — fine as long as nothing in
        // tests inserts 'every_trimester_after_first' / 'on_course_completion' /
        // 'on_graduation_processing' into course_fee_plans.charge_timing.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE course_fee_plans
                MODIFY COLUMN charge_timing ENUM(
                    'on_enrollment',
                    'every_trimester',
                    'every_trimester_after_first',
                    'specific_trimester',
                    'on_completion',
                    'on_course_completion',
                    'on_graduation_processing'
                ) NOT NULL
            ");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE course_fee_plans
                MODIFY COLUMN charge_timing ENUM(
                    'on_enrollment',
                    'every_trimester',
                    'specific_trimester',
                    'on_completion'
                ) NOT NULL
            ");
        }
    }
};
