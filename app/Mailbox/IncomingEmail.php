<?php

namespace App\Mailbox;

use Carbon\CarbonImmutable;

final readonly class IncomingEmail
{
    /**
     * @param  list<string>  $attachments
     */
    public function __construct(
        public string $messageId,
        public ?string $inReplyTo,
        public ?string $senderName,
        public string $senderEmail,
        public string $subject,
        public string $bodyText,
        public array $attachments,
        public CarbonImmutable $receivedAt,
    ) {
    }
}