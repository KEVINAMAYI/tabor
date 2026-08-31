<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sub_vote_heads', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('vote_head_id')->constrained()->restrictOnDelete();
            $table->string('code');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['vote_head_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_vote_heads');
    }
};
