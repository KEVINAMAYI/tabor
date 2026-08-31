<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('enrollment_deferrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enrollment_progression_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('resume_trimester_id')->nullable()->constrained('trimesters')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();

            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');

            $table->datetime('requested_at');
            $table->datetime('approved_at')->nullable();
            $table->datetime('rejected_at')->nullable();
            $table->datetime('resumed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_deferrals');
    }
};
