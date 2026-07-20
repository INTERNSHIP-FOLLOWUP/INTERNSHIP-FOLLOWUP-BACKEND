<?php

namespace Database\Factories;

use App\Models\{Student, User};
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Followup>
 */
class FollowupFactory extends Factory
{
    public function definition(): array
    {
        $pastOrFuture = fake()->boolean(70);
        $scheduledAt = $pastOrFuture
            ? Carbon::now()->subDays(fake()->numberBetween(2, 30))->addHours(fake()->numberBetween(8, 18))
            : Carbon::now()->addDays(fake()->numberBetween(3, 40))->addHours(fake()->numberBetween(8, 18));
        $status = $scheduledAt->isPast() ? 'Completed' : fake()->randomElement(['Scheduled', 'Pending']);

        return [
            'student_id'    => Student::inRandomOrder()->first()?->id,
            'tutor_id'      => User::where('role', 'tutor')->inRandomOrder()->first()?->id,
            'type'          => fake()->randomElement(['Online', 'Offline', 'Phone Call', 'Office Meeting']),
            'scheduled_at'  => $scheduledAt,
            'notes'         => fake()->optional(0.5)->sentence(),
            'status'        => $status,
        ];
    }

    public function dueSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'scheduled_at' => Carbon::now()->addDays(fake()->numberBetween(1, 6))->addHours(fake()->numberBetween(8, 18)),
            'status'       => fake()->randomElement(['Scheduled', 'Pending']),
        ]);
    }
}
