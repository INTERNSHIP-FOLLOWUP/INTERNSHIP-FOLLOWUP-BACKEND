<?php

namespace App\Enums;

enum AssignmentStatus: string
{
    case Assigned = 'Assigned';
    case InProgress = 'In Progress';
    case Completed = 'Completed';
    case Terminated = 'Terminated';

    public function label(): string
    {
        return match ($this) {
            self::Assigned => 'Assigned',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
            self::Terminated => 'Terminated',
        };
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Assigned => [self::InProgress, self::Terminated],
            self::InProgress => [self::Completed, self::Terminated],
            self::Completed => [],
            self::Terminated => [self::Assigned],
        };
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return in_array($newStatus, $this->allowedTransitions(), true);
    }

    /**
     * @return string[] Human-readable transition descriptions.
     */
    public static function values(): array
    {
        return array_map(fn(self $case) => $case->value, self::cases());
    }
}
