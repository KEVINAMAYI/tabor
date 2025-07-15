<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin
        User::factory()->superAdmin()->create([
            'name' => 'Super Admin',
            'email' => 'super@demo.com',
            'password' => bcrypt('password'),
        ]);

        // Admin
        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@demo.com',
            'password' => bcrypt('password'),
        ]);

        // Finance Manager
        User::factory()->financeManager()->create([
            'name' => 'Finance Manager',
            'email' => 'finance@demo.com',
            'password' => bcrypt('password'),
        ]);

        // One known Student
        User::factory()->student()->create([
            'name' => 'Student User',
            'email' => 'student@demo.com',
            'password' => bcrypt('password'),
        ]);

        // Extra random students
        User::factory()->student()->count(10)->create();

        $this->command->info('✅ Demo users seeded with roles and passwords set to "password".');
    }
}
