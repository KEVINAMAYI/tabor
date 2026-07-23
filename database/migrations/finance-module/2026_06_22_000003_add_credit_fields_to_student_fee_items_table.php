<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('student_fee_items', function (Blueprint $table) {
            $table->string('credit_type')->nullable()->after('status');
            $table->foreignId('applied_by')->nullable()->after('credit_type')->constrained('users')->nullOnDelete();
            $table->text('credit_reason')->nullable()->after('applied_by');
        });
    }

    public function down(): void
    {
        Schema::table('student_fee_items', function (Blueprint $table) {
            $table->dropForeign(['applied_by']);
            $table->dropColumn(['credit_type', 'applied_by', 'credit_reason']);
        });
    }
};
