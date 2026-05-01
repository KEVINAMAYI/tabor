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
        Schema::table('students', function (Blueprint $table) {
            $table->decimal('registration_fee', 10, 2)->default(0)->after('passport_size_url');
            $table->decimal('student_id_fee', 10, 2)->default(0)->after('registration_fee');
            $table->decimal('stationery_fee', 10, 2)->default(0)->after('student_id_fee');
            $table->decimal('caution_fee', 10, 2)->default(0)->after('stationery_fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['registration_fee', 'student_id_fee', 'stationery_fee', 'caution_fee']);
        });
    }
};
