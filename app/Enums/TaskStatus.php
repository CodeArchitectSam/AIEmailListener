<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Open = 'Open';
    case NeedsReview = 'Needs Review';
    case InProgress = 'In Progress';
    case Resolved = 'Resolved';
    case Closed = 'Closed';

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

    public function isOpen(): bool
    {
        return in_array($this, [self::Open, self::NeedsReview], true);
    }
}