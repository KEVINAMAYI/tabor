<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin
        if (!User::where('email', 'super@demo.com')->exists()) {
            User::factory()->superAdmin()->create([
                'name' => 'Super Admin',
                'first_name' => 'Super',
                'email' => 'super@demo.com',
                'password' => bcrypt('password'),
                'active' => true,
            ]);

            // Admin
            User::factory()->admin()->create([
                'name' => 'Admin User',
                'first_name' => 'Admin',
                'email' => 'admin@demo.com',
                'password' => bcrypt('password'),
                'active' => true,
            ]);

            // Finance Manager
            User::factory()->financeManager()->create([
                'name' => 'Finance Manager',
                'first_name' => 'Finance',
                'email' => 'finance@demo.com',
                'password' => bcrypt('password'),
                'active' => true,
            ]);

            // One known Student
            User::factory()->student()->create([
                'name' => 'Student User',
                'first_name' => 'Student',
                'email' => 'student@demo.com',
                'password' => bcrypt('password'),
                'active' => true,
            ]);

            // Extra random students
            User::factory()->student()->count(10)->create();

            $this->command->info('✅ Demo users seeded with roles and passwords set to "password".');
        }



    }
}
