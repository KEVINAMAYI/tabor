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
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['CAT', 'Exam']);
            $table->string('title');
            $table->foreignId('intake_module_id')->constrained()->onDelete('cascade');
            $table->date('due_on')->nullable();
            $table->unsignedSmallInteger('max_marks')->default(100);
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};
