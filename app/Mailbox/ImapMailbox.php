<?php

namespace App\Mailbox;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Driver that reads a real IMAP mailbox using the native imap extension.
 * Enable it by setting MAILBOX_DRIVER=imap and filling the IMAP_* env vars.
 */
class ImapMailbox implements MailboxClient
{
    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $username,
        private readonly string $password,
        private readonly string $encryption,
        private readonly bool $validateCert,
        private readonly string $folder,
    ) {
    }

    /**
     * @return Collection<int, IncomingEmail>
     */
    public function fetchUnreadEmails(): Collection
    {
        if (! function_exists('imap_open')) {
            Log::error('The PHP imap extension is not loaded; returning no emails.');

            return collect();
        }

        $flags = $this->validateCert ? '' : '/novalidate-cert';
        $mailbox = '{'.$this->host.':'.$this->port.'/imap/'.($this->encryption === 'ssl' ? 'ssl' : 'notls').$flags.'}'.$this->folder;

        $stream = @imap_open($mailbox, $this->username, $this->password, OP_HALFOPEN);

        if ($stream === false) {
            Log::error('Could not connect to IMAP mailbox: '.(imap_last_error() ?: 'unknown error'));

            return collect();
        }

        try {
            $messages = imap_search($stream, 'UNSEEN');

            if ($messages === false) {
                return collect();
            }

            return collect($messages)
                ->map(fn (int $messageNumber) => $this->toIncomingEmail($stream, $messageNumber))
                ->filter()
                ->values();
        } finally {
            imap_close($stream);
        }
    }

    private function toIncomingEmail(mixed $stream, int $messageNumber): ?IncomingEmail
    {
        $header = imap_headerinfo($stream, $messageNumber);

        if ($header === false) {
            return null;
        }

        $body = $this->fetchPlainTextBody($stream, $messageNumber);

        imap_setflag_full($stream, (string) $messageNumber, '\\Seen');

        [$senderName, $senderEmail] = $this->extractFrom($header);

        return new IncomingEmail(
            messageId: $this->cleanMessageId($header->message_id ?? (string) $messageNumber),
            inReplyTo: $this->cleanMessageId($header->in_reply_to ?? null),
            senderName: $senderName,
            senderEmail: $senderEmail,
            subject: mb_decode_mimeheader(imap_utf8((string) ($header->subject ?? ''))),
            bodyText: $body,
            attachments: [],
            receivedAt: CarbonImmutable::createFromTimestamp($header->udate ?? time()),
        );
    }

    private function fetchPlainTextBody(mixed $stream, int $messageNumber): string
    {
        $structure = imap_fetchstructure($stream, $messageNumber);

        if ($structure === false || ! isset($structure->parts)) {
            $body = imap_body($stream, $messageNumber);

            return $body === false ? '' : imap_qprint($body);
        }

        foreach ($structure->parts as $index => $part) {
            if (strtolower((string) ($part->subtype ?? '')) === 'plain' && strtolower((string) ($part->type ?? 0)) === '0') {
                $body = imap_fetchbody($stream, $messageNumber, (string) ($index + 1));

                return $body === false ? '' : imap_qprint($body);
            }
        }

        return '';
    }

    /**
     * @return array{0: ?string, 1: string} name and email
     */
    private function extractFrom(object $header): array
    {
        $from = $header->from[0] ?? null;

        if ($from === null) {
            return [null, ''];
        }

        $name = isset($from->personal)
            ? mb_decode_mimeheader(imap_utf8((string) $from->personal))
            : null;

        return [$name, strtolower($from->mailbox.'@'.$from->host)];
    }

    private function cleanMessageId(?string $messageId): ?string
    {
        if ($messageId === null || trim($messageId) === '') {
            return null;
        }

        return trim(trim($messageId), '<>');
    }
}