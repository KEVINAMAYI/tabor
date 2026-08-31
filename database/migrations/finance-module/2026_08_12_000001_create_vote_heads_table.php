<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vote_heads', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            // Which expense account (Chart of Accounts) petty cash expenses
            // under this vote head post against, e.g. "ICT" vote head -> 5050.
            $table->foreignId('expense_account_id')->constrained('chart_of_accounts')->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vote_heads');
    }
};
