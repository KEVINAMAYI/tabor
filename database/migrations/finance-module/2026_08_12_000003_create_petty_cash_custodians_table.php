<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('petty_cash_custodians', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->restrictOnDelete();
            // Free-text role/title (e.g. "Registrar", "Procurement Officer") —
            // no structured Department model exists in this codebase yet.
            $table->string('title')->nullable();
            $table->decimal('opening_float', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petty_cash_custodians');
    }
};
