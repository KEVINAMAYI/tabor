<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('po_number')->unique();
            // Unique: single-line requisition -> single PO for v1 (see Phase 4
            // plan C.1 — a genuine split-delivery/split-invoice PO isn't
            // representable without line-item quantities).
            $table->foreignId('purchase_requisition_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->text('description');
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['open', 'received', 'cancelled'])->default('open');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('document_path')->nullable();
            $table->string('document_original_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
