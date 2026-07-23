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
        Schema::table('student_fee_items', function (Blueprint $table) {
            $table->foreignId('enrollment_progression_id')
                ->nullable()
                ->after('enrollment_id')
                ->constrained('enrollment_progressions')
                ->nullOnDelete();

            $table->index(['enrollment_progression_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_fee_items', function (Blueprint $table) {
            $table->dropForeign(['enrollment_progression_id']);
            $table->dropIndex(['enrollment_progression_id']);
            $table->dropColumn('enrollment_progression_id');
        });
    }
};
