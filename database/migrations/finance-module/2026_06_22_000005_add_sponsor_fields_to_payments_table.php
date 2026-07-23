<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'sponsored_by')) {
                $table->string('sponsored_by')->nullable()->after('notes');
            }

            if (!Schema::hasColumn('payments', 'sponsor_reference')) {
                $table->string('sponsor_reference')->nullable()->after('sponsored_by');
            }

            if (!Schema::hasColumn('payments', 'is_sponsored')) {
                $table->boolean('is_sponsored')->default(false)->after('sponsor_reference');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['sponsored_by', 'sponsor_reference', 'is_sponsored']);
        });
    }
};
