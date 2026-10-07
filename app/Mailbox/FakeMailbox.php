<?php

namespace App\Mailbox;

use App\Models\FakeMailboxEmail;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Mailbox driver that pulls sample emails seeded into the database.
 * Lets the whole pipeline run without a real IMAP account.
 */
class FakeMailbox implements MailboxClient
{
    /**
     * @return Collection<int, IncomingEmail>
     */
    public function fetchUnreadEmails(): Collection
    {
        return FakeMailboxEmail::query()
            ->whereNull('consumed_at')
            ->orderBy('received_at')
            ->get()
            ->map(function (FakeMailboxEmail $mail) {
                $mail->update(['consumed_at' => now()]);

                return new IncomingEmail(
                    messageId: $mail->message_id,
                    inReplyTo: $mail->in_reply_to,
                    senderName: $mail->sender_name,
                    senderEmail: $mail->sender_email,
                    subject: $mail->subject,
                    bodyText: $mail->body_text,
                    attachments: $mail->attachments ?? [],
                    receivedAt: CarbonImmutable::instance($mail->received_at),
                );
            })
            ->values();
    }
}