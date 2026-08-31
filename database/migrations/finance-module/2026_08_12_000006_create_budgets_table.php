<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('financial_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('vote_head_id')->constrained()->restrictOnDelete();
            // Null = budget for the whole vote head. Set = budget scoped to
            // one sub-vote-head specifically (see BudgetReportService for how
            // actual expenditure is matched to each case).
            $table->foreignId('sub_vote_head_id')->nullable()->constrained('sub_vote_heads')->nullOnDelete();
            $table->decimal('budgeted_amount', 12, 2);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['financial_year_id', 'vote_head_id', 'sub_vote_head_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
