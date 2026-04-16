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
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'student_id')) {
                $table->foreignId('student_id')->nullable()->after('id')->constrained()->nullOnDelete();
            }

            if (!Schema::hasColumn('payments', 'enrollment_id')) {
                $table->foreignId('enrollment_id')->nullable()->after('student_id')->constrained()->nullOnDelete();
            }

            if (!Schema::hasColumn('payments', 'payment_date')) {
                $table->date('payment_date')->nullable()->after('enrollment_id');
            }

            if (!Schema::hasColumn('payments', 'method')) {
                $table->string('method')->nullable()->after('amount');
            }

            if (!Schema::hasColumn('payments', 'reference')) {
                $table->string('reference')->nullable()->after('method');
            }

            if (!Schema::hasColumn('payments', 'receipt_no')) {
                $table->string('receipt_no')->nullable()->after('reference');
            }

            if (!Schema::hasColumn('payments', 'status')) {
                $table->string('status')->default('completed')->after('receipt_no');
            }

            if (!Schema::hasColumn('payments', 'notes')) {
                $table->text('notes')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        
    }
};
