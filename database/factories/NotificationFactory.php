<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        $types = ['general', 'assignment', 'validation', 'worklog', 'evaluation', 'followup', 'issue', 'reminder', 'system'];
        $priorities = ['low', 'normal', 'high', 'urgent'];

        return [
            'sender_id' => User::inRandomOrder()->first()?->id,
            'receiver_id' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'title' => fake()->sentence(6),
            'message' => fake()->paragraph(2),
            'type' => fake()->randomElement($types),
            'priority' => fake()->randomElement($priorities),
            'entity_type' => fake()->randomElement(['worklog', 'followup', 'issue', 'assignment', 'evaluation', null]),
            'entity_id' => fake()->randomNumber(5, true),
            'action_url' => fake()->optional()->url(),
            'is_read' => fake()->boolean(30),
            'read_at' => fake()->optional()->dateTime(),
        ];
    }

    public function unread(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    public function read(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => true,
            'read_at' => fake()->dateTime(),
        ]);
    }

    public function ofType(string $type): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }

    public function ofPriority(string $priority): self
    {
        return $this->state(fn (array $attributes) => [
            'priority' => $priority,
        ]);
    }

    public function forUser(int $userId): self
    {
        return $this->state(fn (array $attributes) => [
            'receiver_id' => $userId,
        ]);
    }
}