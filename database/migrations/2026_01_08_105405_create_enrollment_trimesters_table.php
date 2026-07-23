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
        Schema::create('course_trimesters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->integer('trimester_number');
            $table->integer('duration_months');
            $table->decimal('fee_amount', 10, 2);
            $table->timestamps();
        });


        Schema::create('enrollment_trimesters', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('trimester_number')->nullable();
            $table->foreignId('enrollment_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_trimester_id')->nullable()->constrained()->onDelete('cascade');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('status', ['pending', 'in-progress', 'completed'])->nullable();
            $table->decimal('fee_amount', 10, 2)->default(0);
            $table->decimal('fee_paid', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('enrollment_trimester_id')->nullable()->constrained()->onDelete('cascade')->after('enrollment_id');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->string('number_of_trimesters')->nullable()->after('duration');
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->string('expected_completion_date')->nullable()->after('enrolled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollment_trimesters');
    }
};
