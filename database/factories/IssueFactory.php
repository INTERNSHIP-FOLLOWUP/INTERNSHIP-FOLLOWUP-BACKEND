<?php

namespace Database\Factories;

use App\Models\Issue;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class IssueFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id'  => Student::inRandomOrder()->first()?->id,
            'tutor_id'    => User::where('role', 'tutor')->inRandomOrder()->first()?->id,
            'title'       => fake()->sentence(6),
            'description' => fake()->realText(240),
            'status'      => fake()->randomElement(['Open', 'In Progress', 'In Progress', 'Resolved', 'Closed']),
            'priority'    => fake()->randomElement(['Low', 'Medium', 'Medium', 'High', 'Critical']),
        ];
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'Open']);
    }

    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'High',
            'status'   => fake()->randomElement(['Open', 'In Progress']),
        ]);
    }
}
