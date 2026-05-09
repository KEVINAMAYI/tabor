<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {

            if (!Schema::hasColumn('payments', 'unallocated_balance')) {
                $table->decimal('unallocated_balance', 12, 2)
                    ->default(0)
                    ->after('amount');
            }

            if (!Schema::hasColumn('payments', 'method')) {
                $table->string('method')
                    ->nullable()
                    ->after('unallocated_balance');
            }

            if (!Schema::hasColumn('payments', 'notes')) {
                $table->text('notes')
                    ->nullable()
                    ->after('status');
            }
        });

        /*
        |--------------------------------------------------------------------------
        | DATA CLEANUP
        |--------------------------------------------------------------------------
        */

        // migrate old payment_method -> method
        if (
            Schema::hasColumn('payments', 'payment_method') &&
            Schema::hasColumn('payments', 'method')
        ) {
            DB::statement("
                UPDATE payments
                SET method = COALESCE(method, payment_method)
                WHERE method IS NULL
            ");
        }

        // fill missing payment_date
        if (Schema::hasColumn('payments', 'payment_date')) {
            DB::statement("
                UPDATE payments
                SET payment_date = COALESCE(
                    payment_date,
                    DATE(paid_at),
                    DATE(created_at)
                )
                WHERE payment_date IS NULL
            ");
        }

        // initialize unallocated balance
        if (Schema::hasColumn('payments', 'unallocated_balance')) {
            DB::statement("
                UPDATE payments
                SET unallocated_balance = amount
                WHERE unallocated_balance IS NULL
            ");
        }
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {

            if (Schema::hasColumn('payments', 'unallocated_balance')) {
                $table->dropColumn('unallocated_balance');
            }

            if (Schema::hasColumn('payments', 'method')) {
                $table->dropColumn('method');
            }
        });
    }
};
