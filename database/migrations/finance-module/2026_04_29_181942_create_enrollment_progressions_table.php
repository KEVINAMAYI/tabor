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
        Schema::create('enrollment_progressions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trimester_id')->constrained()->cascadeOnDelete();

            $table->unsignedTinyInteger('trimester_sequence');

            $table->enum('status', [
                'upcoming',
                'active',
                'completed',
                'deferred',
                'repeated',
                'cancelled',
            ])->default('upcoming');

            $table->date('started_at')->nullable();
            $table->date('completed_at')->nullable();

            $table->unique(['enrollment_id', 'trimester_sequence'], 'enrollment_progression_sequence_unique');
            $table->unique(['enrollment_id', 'trimester_id'], 'enrollment_progression_trimester_unique');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollment_progressions');
    }
};
