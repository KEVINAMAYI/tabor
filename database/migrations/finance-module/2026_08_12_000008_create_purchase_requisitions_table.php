<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('purchase_requisitions', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('requisition_number')->unique();
            $table->foreignId('vote_head_id')->constrained()->restrictOnDelete();
            $table->foreignId('sub_vote_head_id')->nullable()->constrained('sub_vote_heads')->nullOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('description');
            $table->decimal('estimated_amount', 12, 2);
            $table->date('needed_by_date')->nullable();

            // No Department/org-unit model exists in this codebase — "Department
            // Approval" is a second permission-gated signature on this same
            // record, not routed to a specific department. See Phase 4 plan C.1.
            $table->enum('status', ['draft', 'submitted', 'dept_approved', 'finance_approved', 'rejected', 'converted'])
                ->default('draft');
            $table->foreignId('dept_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dept_approved_at')->nullable();
            $table->foreignId('finance_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finance_approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();

            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_requisitions');
    }
};
