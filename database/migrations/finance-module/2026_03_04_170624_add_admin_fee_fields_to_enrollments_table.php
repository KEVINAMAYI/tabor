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
        Schema::table('enrollments', function (Blueprint $table) {
                $table->boolean('include_registration_fee')->default(false)->after('remarks');
                $table->boolean('include_student_id_fee')->default(false)->after('include_registration_fee');
                $table->boolean('include_stationery_fee')->default(false)->after('include_student_id_fee');
                $table->boolean('include_caution_fee')->default(false)->after('include_stationery_fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn(['include_registration_fee', 'include_student_id_fee', 'include_stationery_fee', 'include_caution_fee']);
        });
    }
};
