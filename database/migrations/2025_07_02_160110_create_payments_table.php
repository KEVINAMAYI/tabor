<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->nullable()->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2)->default(0.00);
            $table->enum('payment_method', ['cash','mpesa','discount','card','bank'])->default('cash');
            $table->enum('status', ['pending','completed','failed'])->default('pending');
            $table->string('transaction_id')->nullable();
            $table->string('reference')->nullable();
            $table->string('payer',255)->nullable();
            $table->text('phone')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
