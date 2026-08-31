<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Resolves the free-text `payments.method` column (cash/mpesa/bank/credit/...)
     * to a real Chart of Accounts account for GL posting, without altering the
     * live `payments` table.
     */
    public function up(): void
    {
        Schema::create('payment_method_account_map', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('method')->unique();
            $table->foreignId('account_id')->constrained('chart_of_accounts')->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_method_account_map');
    }
};
