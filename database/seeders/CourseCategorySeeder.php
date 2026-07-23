<?php

namespace Database\Seeders;

use App\Models\CourseCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CourseCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Healthcare',
            'Languages',
            'Hospitality',
            'Beauty',
            'ICT',
            'Mental Health',
            'Building & Construction',
            'Engineering',
            'Automotive & Logistics',
            'Business'
            ];

        foreach ($categories as $category) {
            CourseCategory::firstOrCreate([
                'name' => $category,
                'slug' => Str::slug($category),
                'description' => $category . ' related courses'
            ]);
        }
    }
}
