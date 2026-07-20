<?php

namespace Database\Factories;

use App\Models\Worklog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'commentable_type' => Worklog::class,
            'commentable_id'   => Worklog::inRandomOrder()->first()?->id ?? 1,
            'user_id'          => \App\Models\User::where('role', 'tutor')->inRandomOrder()->first()?->id,
            'body'             => fake()->realText(180),
        ];
    }
}
