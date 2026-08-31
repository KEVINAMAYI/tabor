<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('financial_years', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('name'); // e.g. FY2026
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_years');
    }
};
