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
        Schema::create('student_fee_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enrollment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('trimester_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('fee_definition_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('course_fee_plan_id')->nullable()->constrained()->nullOnDelete();

            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('balance', 12, 2)->default(0);
            $table->date('charge_date');
            $table->date('due_date')->nullable();

            $table->enum('status', ['pending', 'partial', 'paid', 'waived', 'cancelled'])->default('pending');
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_fee_items');
    }
};
