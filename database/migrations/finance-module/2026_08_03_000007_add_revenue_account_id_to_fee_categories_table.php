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
        Schema::table('fee_categories', function (Blueprint $table) {
            $table->foreignId('revenue_account_id')->nullable()->after('name')
                ->constrained('chart_of_accounts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fee_categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('revenue_account_id');
        });
    }
};
