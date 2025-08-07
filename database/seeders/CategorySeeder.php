<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Basic'],
            ['name' => 'Common'],
            ['name' => 'Core'],
            ['name' => 'Clinical'],
            ['name' => 'Indstrial Attachment'],
        ];

        foreach ($categories as $category) {
           Category::firstOrCreate($category);
        }
    }
}
