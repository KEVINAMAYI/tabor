<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->boolean('apply_exam_fee')->default(false);

            $table->boolean('apply_attachment_fee')->default(false);
            $table->unsignedBigInteger('attachment_trimester_sequence')->nullable()->after('apply_attachment_fee');

            $table->boolean('apply_graduation_fee')->default(false);
            $table->decimal('graduation_fee', 10, 2)->default(0.00);

            $table->boolean('apply_certification_fee')->default(false);
            $table->decimal('certification_fee', 10, 2)->default(0.00);

            $table->enum('course_type', ['short','medium','long'])->default('medium')->after('code');
            $table->boolean('active')->default(true)->after('course_type');
            $table->boolean('allows_continuous_intake')->default(false)->after('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {

            // IMPORTANT:
            // Only drop columns that were added by this migration.
            // If exam_fee / attachment_fee are original columns in your schema, DO NOT drop them here.

            $table->dropColumn([
                'apply_exam_fee',

                'apply_attachment_fee',

                'apply_graduation_fee',
                'graduation_fee',

                'apply_certification_fee',
                'certification_fee',

                'course_type',
                'active',
                'allows_continuous_intake',
            ]);
        });
    }
};
