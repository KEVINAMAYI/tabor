<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->unsignedBigInteger('default_lecturer_id')->nullable()->after('course_id');

            $table->foreign('default_lecturer_id')
                ->references('id')->on('lecturers')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropForeign(['default_lecturer_id']);
            $table->dropColumn('default_lecturer_id');
        });
    }
};

