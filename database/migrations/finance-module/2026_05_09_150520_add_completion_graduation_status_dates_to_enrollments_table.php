<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            if (!Schema::hasColumn('enrollments', 'course_completed_at')) {
                $table->timestamp('course_completed_at')->nullable()->after('status');
            }

            if (!Schema::hasColumn('enrollments', 'pending_graduation_at')) {
                $table->timestamp('pending_graduation_at')->nullable()->after('course_completed_at');
            }

            if (!Schema::hasColumn('enrollments', 'graduated_at')) {
                $table->timestamp('graduated_at')->nullable()->after('pending_graduation_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            //
        });
    }
};
