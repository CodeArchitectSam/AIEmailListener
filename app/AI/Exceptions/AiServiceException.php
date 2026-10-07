<?php

namespace App\AI\Exceptions;

use RuntimeException;

/**
 * Raised when the AI provider fails at the network/API level (timeout,
 * HTTP error, empty reply). The job retries until attempts run out.
 */
class AiServiceException extends RuntimeException
{
}