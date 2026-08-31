<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('intake_modules', function (Blueprint $table) {
            $table->foreignId('trimester_id')->nullable()->after('intake_id')->constrained('trimesters')->nullOnDelete();
        });

        $trimesters = DB::table('trimesters')->select('id', 'start_date', 'end_date')->get()->map(function ($t) {
            $t->start_date = Carbon::parse($t->start_date);
            $t->end_date = Carbon::parse($t->end_date);
            return $t;
        });

        $intakes = DB::table('intakes')->select('id', 'starts_at')->get()->keyBy('id');

        DB::table('intake_modules')->select('id', 'intake_id')->orderBy('id')->get()->each(function ($im) use ($trimesters, $intakes) {
            $intake = $intakes->get($im->intake_id);

            if (!$intake || !$intake->starts_at) {
                return;
            }

            $intakeStart = Carbon::parse($intake->starts_at);

            $match = $trimesters->first(
                fn($t) => $intakeStart->gte($t->start_date) && $intakeStart->lte($t->end_date)
            );

            if ($match) {
                DB::table('intake_modules')->where('id', $im->id)->update(['trimester_id' => $match->id]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('intake_modules', function (Blueprint $table) {
            $table->dropForeign(['trimester_id']);
            $table->dropColumn('trimester_id');
        });
    }
};
