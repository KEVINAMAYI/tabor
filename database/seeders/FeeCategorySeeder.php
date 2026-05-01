<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\FeeCategory;

class FeeCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /* $categories = [
            ['name' => 'Tuition', 'code' => 'TUITION'],
            ['name' => 'Library', 'code' => 'LIBRARY'],
            ['name' => 'Laboratory', 'code' => 'LABORATORY'],
            ['name' => 'Sports', 'code' => 'SPORTS'],
            ['name' => 'Miscellaneous', 'code' => 'MISC'],
        ];

        foreach ($categories as $category) {
            \App\Models\FeeCategory::create($category);
        } */

        FeeCategory::updateOrCreate(['code' => 'tuition'], ['name' => 'Tuition']);
        FeeCategory::updateOrCreate(['code' => 'student_once'], ['name' => 'Student Once Fee']);
        FeeCategory::updateOrCreate(['code' => 'course_charge'], ['name' => 'Course Charge']);
        FeeCategory::updateOrCreate(['code' => 'exam'], ['name' => 'Exam']);
        FeeCategory::updateOrCreate(['code' => 'adjustment'], ['name' => 'Adjustment']);
    }
}
