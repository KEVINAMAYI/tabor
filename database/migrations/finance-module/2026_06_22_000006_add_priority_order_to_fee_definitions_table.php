<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('fee_definitions', function (Blueprint $table) {
            $table->unsignedTinyInteger('priority_order')->default(10)->after('active');
        });

        // Seed sensible defaults: student-once fees get highest priority (1),
        // everything else stays at 10. Admins can adjust per fee definition.
        DB::table('fee_definitions')
            ->where('scope', 'student')
            ->where('applies_once', true)
            ->update(['priority_order' => 1]);

        DB::table('fee_definitions')
            ->where('scope', '!=', 'student')
            ->orWhere('applies_once', false)
            ->update(['priority_order' => 5]);
    }

    public function down(): void
    {
        Schema::table('fee_definitions', function (Blueprint $table) {
            $table->dropColumn('priority_order');
        });
    }
};
