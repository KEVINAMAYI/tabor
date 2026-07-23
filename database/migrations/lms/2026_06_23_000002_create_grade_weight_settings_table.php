<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_weight_settings', function (Blueprint $table) {
            $table->id();
            $table->string('assessment_type');   // CAT, Assignment, Exam, Quiz
            $table->string('label');             // e.g. "CAT", "End-Term Exam"
            $table->decimal('weight_percentage', 5, 2); // e.g. 30.00, 70.00
            $table->unsignedTinyInteger('max_per_module')->nullable(); // null = unlimited
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed institute-wide defaults: CAT 30%, Exam 70%
        DB::table('grade_weight_settings')->insert([
            [
                'assessment_type'  => 'CAT',
                'label'            => 'CAT',
                'weight_percentage'=> 30.00,
                'max_per_module'   => null,
                'sort_order'       => 1,
                'is_active'        => true,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'assessment_type'  => 'Exam',
                'label'            => 'End-Term Exam',
                'weight_percentage'=> 70.00,
                'max_per_module'   => 1,
                'sort_order'       => 2,
                'is_active'        => true,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_weight_settings');
    }
};
