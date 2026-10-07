<?php

namespace App\Enums;

enum Category: string
{
    case HVAC = 'HVAC';
    case Electrical = 'Electrical';
    case Plumbing = 'Plumbing';
    case Civil = 'Civil';
    case Cleaning = 'Cleaning';
    case PestControl = 'PestControl';
    case Lifts = 'Lifts';
    case FireAndSafety = 'Fire & Safety';
    case Other = 'Other';

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
}