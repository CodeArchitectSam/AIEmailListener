<?php

namespace App\AI\Exceptions;

use RuntimeException;

/**
 * Raised when the AI response is missing, not valid JSON, or does not
 * match the expected schema. The job retries until attempts run out.
 */
class InvalidAiResponseException extends RuntimeException
{
    public static function forField(string $field, string $reason = ''): self
    {
        $reason = $reason !== '' ? " ({$reason})" : '';

        return new self("AI response field [{$field}] is invalid{$reason}.");
    }
}