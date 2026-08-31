<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('petty_cash_expenses', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('custodian_id')->constrained('petty_cash_custodians')->restrictOnDelete();
            $table->foreignId('vote_head_id')->constrained()->restrictOnDelete();
            $table->foreignId('sub_vote_head_id')->nullable()->constrained('sub_vote_heads')->nullOnDelete();
            // No Supplier/Vendor master exists in this codebase yet (that
            // arrives with the Procurement phase) — free text for now.
            $table->string('supplier_name')->nullable();
            $table->text('description');
            $table->decimal('amount', 12, 2);
            $table->date('expense_date');
            $table->string('receipt_path')->nullable();
            $table->string('receipt_original_name')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->timestamps();

            $table->index(['status']);
            $table->index(['expense_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petty_cash_expenses');
    }
};
