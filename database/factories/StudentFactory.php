<?php

namespace Database\Factories;

use App\Models\{Batch, User};
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();
        $name = $firstName . ' ' . $lastName;
        $email = fake()->unique()->safeEmail();

        return [
            'user_id'      => User::factory()->student(),
            'student_code' => 'STU-' . strtoupper(Str::random(6)),
            'batch_id'     => Batch::inRandomOrder()->first()?->id,
            'tutor_id'     => User::whereIn('role', ['student', 'tutor'])->inRandomOrder()->first()?->id,
            'name'         => $name,
            'gender'       => fake()->randomElement(['Male', 'Female']),
            'phone'        => fake()->phoneNumber(),
            'email'        => $email,
            'photo'        => null,
            'status'       => fake()->randomElement(['active', 'active', 'active', 'inactive']),
        ];
    }
}
