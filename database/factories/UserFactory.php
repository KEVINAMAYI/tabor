<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'first_name' => $this->faker->firstName(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => bcrypt('password'), // use Hash::make() if needed
            'remember_token' => Str::random(10),
        ];
    }

    public function superAdmin()
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('super-admin');
        });
    }

    public function admin()
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('admin');
        });
    }

    public function financeManager()
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('finance-manager');
        });
    }

    public function student()
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('student');
        });
    }
}
