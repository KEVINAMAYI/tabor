<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('supplier_invoices', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            // The supplier's own invoice number (free text), not our sequence.
            $table->string('invoice_number');
            $table->foreignId('purchase_order_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->string('invoice_document_path')->nullable();
            $table->string('invoice_document_original_name')->nullable();
            $table->enum('status', ['pending', 'partially_paid', 'paid', 'cancelled'])->default('pending');
            // The accrual journal entry (DR expense / CR Accounts Payable).
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['supplier_id', 'invoice_number']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_invoices');
    }
};
