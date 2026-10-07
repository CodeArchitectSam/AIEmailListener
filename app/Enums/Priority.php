<?php

namespace App\Enums;

enum Priority: string
{
    case Emergency = 'Emergency';
    case High = 'High';
    case Medium = 'Medium';
    case Low = 'Low';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }

    public static function tryFromCaseInsensitive(string $value): ?self
    {
        foreach (self::cases() as $case) {
            if (strcasecmp($case->value, $value) === 0) {
                return $case;
            }
        }

        return null;
    }

    /**
     * SLA window in hours, used for the optional due-date feature.
     */
    public function slaHours(): int
    {
        return match ($this) {
            self::Emergency => 2,
            self::High => 24,
            self::Medium => 72,
            self::Low => 120,
        };
    }
}