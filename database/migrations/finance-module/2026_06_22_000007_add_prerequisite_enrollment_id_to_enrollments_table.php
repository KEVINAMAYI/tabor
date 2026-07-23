<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreignId('prerequisite_enrollment_id')
                ->nullable()
                ->after('course_id')
                ->constrained('enrollments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropForeign(['prerequisite_enrollment_id']);
            $table->dropColumn('prerequisite_enrollment_id');
        });
    }
};
