<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('goods_received_notes', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('grn_number')->unique();
            // Unique: v1 assumes one full delivery per PO (see purchase_orders
            // migration comment). No Inventory model exists yet — this is a
            // receipt-confirmation/audit-trail record only, not a stock update.
            $table->foreignId('purchase_order_id')->unique()->constrained()->restrictOnDelete();
            $table->date('received_date');
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->string('delivery_note_path')->nullable();
            $table->string('delivery_note_original_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_received_notes');
    }
};
