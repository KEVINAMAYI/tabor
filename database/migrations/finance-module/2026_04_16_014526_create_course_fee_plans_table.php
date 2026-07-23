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
        Schema::create('course_fee_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_definition_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('trimester_sequence')->nullable();
            $table->enum('charge_timing', [
                'on_enrollment',
                'every_trimester',
                'specific_trimester',
                'on_completion',
            ]);
            $table->decimal('amount', 12, 2);
            $table->boolean('mandatory')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_fee_plans');
    }
};
