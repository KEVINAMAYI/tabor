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
        Schema::create('academic_trimesters', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->string(column: 'intake')->nullable();
            $table->string(column: 'trimester')->nullable();
            $table->date(column: 'teaching_start_date')->nullable();
            $table->date(column: 'teaching_end_date')->nullable();
            $table->date(column: 'holiday_start_date')->nullable();
            $table->date(column: 'holiday_end_date')->nullable();
            $table->timestamps();
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->boolean('uses_academic_calendar')->default(true)->after('number_of_trimesters');
        });

        Schema::table('enrollment_trimesters', function (Blueprint $table) {
            $table->foreignId('academic_trimester_id')->nullable()->after('course_trimester_id');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_trimesters');
    }
};
