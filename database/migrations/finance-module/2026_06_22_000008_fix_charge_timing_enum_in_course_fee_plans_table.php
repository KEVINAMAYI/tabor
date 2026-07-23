<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // MySQL requires rebuilding the column to expand an enum
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

    public function down(): void
    {
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
};
