<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Widen the type column to varchar so we can use any string value
        // (Assignment, Quiz) without needing repeated enum migrations.
        // MySQL-only syntax; guarded so a fresh sqlite migrate (e.g. the test
        // suite) doesn't error. Known gap: on sqlite the column keeps its
        // original narrower CHECK constraint from create_assessments_table.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE assessments MODIFY COLUMN type VARCHAR(50) NOT NULL DEFAULT 'CAT'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE assessments MODIFY COLUMN type ENUM('CAT','Exam') NOT NULL DEFAULT 'CAT'");
        }
    }
};
