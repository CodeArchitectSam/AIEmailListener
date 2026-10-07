<?php

namespace App\Enums;

enum EmailStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Analyzed = 'analyzed';
    case Ignored = 'ignored';
    case Failed = 'failed';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}