<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Widen the type column to varchar so we can use any string value
        // (Assignment, Quiz) without needing repeated enum migrations.
        DB::statement("ALTER TABLE assessments MODIFY COLUMN type VARCHAR(50) NOT NULL DEFAULT 'CAT'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE assessments MODIFY COLUMN type ENUM('CAT','Exam') NOT NULL DEFAULT 'CAT'");
    }
};
