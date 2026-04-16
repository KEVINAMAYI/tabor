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
        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreignId('assigned_start_trimester_id')->after('intake_trimester_id')->nullable()->constrained('trimesters')->nullOnDelete();
            $table->date('admission_date')->nullable()->after('intake_trimester_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {

            $table->dropForeign(['assigned_start_trimester_id']);
            $table->dropColumn('assigned_start_trimester_id');

            $table->dropColumn('admission_date');
        });
    }
};
