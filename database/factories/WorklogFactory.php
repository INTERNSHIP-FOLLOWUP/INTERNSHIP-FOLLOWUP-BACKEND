<?php

namespace Database\Factories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Worklog>
 */
class WorklogFactory extends Factory
{
    public function definition(): array
    {
        $weeksAgo = fake()->numberBetween(0, 4);
        $submissionDate = Carbon::now()->subWeeks($weeksAgo)->toDateString();

        return [
            'student_id'      => Student::inRandomOrder()->first()?->id,
            'week_number'     => fake()->numberBetween(1, 12),
            'description'     => fake()->realText(220),
            'challenges'      => fake()->optional(0.7)->realText(160),
            'submission_date' => $submissionDate,
            'status'          => 'Submitted',
            'feedback'        => fake()->optional()->realText(180),
            'reviewer_id'     => null,
            'reviewed_at'     => null,
        ];
    }

    public function reviewed(): static
    {
        return $this->state(function (array $attributes) {
            $reviewedAt = Carbon::parse($attributes['submission_date'] ?? now())->addDays(fake()->numberBetween(1, 4));

            return [
                'status' => 'Reviewed',
                'reviewer_id' => \App\Models\User::where('role', 'tutor')->inRandomOrder()->first()?->id,
                'reviewed_at' => $reviewedAt,
            ];
        });
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'      => 'Submitted',
            'reviewer_id' => null,
            'reviewed_at' => null,
        ]);
    }
}
