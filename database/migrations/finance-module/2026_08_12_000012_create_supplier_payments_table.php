<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('supplier_invoice_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('payment_date');
            $table->string('method');
            $table->string('reference')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            // The settlement journal entry (DR Accounts Payable / CR cash-bank account).
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['supplier_invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
    }
};
