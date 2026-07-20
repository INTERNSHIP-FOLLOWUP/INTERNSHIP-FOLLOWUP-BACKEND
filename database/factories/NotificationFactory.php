<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    public function definition(): array
    {
        $notifiableTypes = [
            \App\Models\User::class,
        ];
        $notifiableType = fake()->randomElement($notifiableTypes);
        $userId = null;

        if ($notifiableType === \App\Models\User::class) {
            $user = \App\Models\User::inRandomOrder()->first();
            if ($user) {
                $userId = $user->id;
            }
        }

        return [
            'user_id'        => $userId,
            'type'           => fake()->randomElement(['worklog_reviewed', 'issue_updated', 'followup_reminder']),
            'notifiable_type' => $notifiableType,
            'notifiable_id'   => $userId,
            'data'           => [
                'message' => fake()->sentence(),
                'link'    => fake()->optional()->url(),
            ],
            'read_at'        => fake()->optional()->dateTimeThisMonth(),
        ];
    }
}
