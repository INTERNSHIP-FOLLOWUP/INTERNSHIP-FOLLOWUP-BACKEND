<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'first_name'        => fake()->firstName(),
            'last_name'         => fake()->lastName(),
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => static::$password ??= Hash::make('password'),
            'theme'             => 'light',
            'remember_token'    => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function tutor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::where('name', 'tutor')->value('id'),
        ]);
    }

    public function student(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::where('name', 'student')->value('id'),
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::where('name', 'admin')->value('id'),
        ]);
    }

    public function companyRep(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::where('name', 'company')->value('id'),
        ]);
    }
}
