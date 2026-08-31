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
        Schema::create('course_applications', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('preferred_trimester_id')->nullable()->constrained('trimesters')->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email');
            $table->string('phone_number');
            $table->text('address')->nullable();
            $table->string('country')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('highest_level_of_education')->nullable();
            $table->string('id_number')->nullable();
            $table->string('id_url')->nullable();
            $table->string('kcse_certificate')->nullable();
            $table->string('passport_size_url')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('promoted_student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->foreignId('promoted_enrollment_id')->nullable()->constrained('enrollments')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_applications');
    }
};
