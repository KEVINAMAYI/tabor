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
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('accounting_period_id')->constrained()->restrictOnDelete();
            $table->date('entry_date');
            $table->string('reference')->nullable();
            $table->text('description');

            // Polymorphic link back to the record that caused this entry
            // (Payment, PaymentRefund, StudentFeeItem), null for manual entries.
            $table->nullableMorphs('source');

            $table->enum('status', ['draft', 'posted', 'reversed'])->default('draft');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('posted_at')->nullable();

            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('reversal_journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();

            $table->timestamps();

            $table->index(['entry_date']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
