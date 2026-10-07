<?php

namespace App\Console\Commands;

use App\Jobs\AnalyzeEmailJob;
use App\Mailbox\MailboxClient;
use App\Models\Email;
use Illuminate\Console\Command;

class FetchEmailsCommand extends Command
{
    protected $signature = 'emails:fetch';

    protected $description = 'Fetch unread emails from the mailbox and dispatch an analysis job per new email';

    public function handle(MailboxClient $mailbox): int
    {
        $incoming = $mailbox->fetchUnreadEmails();

        $fetched = 0;
        $duplicates = 0;

        foreach ($incoming as $message) {
            $exists = Email::query()->where('message_id', $message->messageId)->first();

            if ($exists !== null) {
                $duplicates++;
                continue;
            }

            $email = Email::create([
                'message_id' => $message->messageId,
                'in_reply_to' => $message->inReplyTo,
                'sender_name' => $message->senderName,
                'sender_email' => $message->senderEmail,
                'subject' => $message->subject,
                'body_text' => $message->bodyText,
                'attachments' => $message->attachments,
                'received_at' => $message->receivedAt,
            ]);

            AnalyzeEmailJob::dispatch($email);

            $fetched++;
        }

        $this->info("emails:fetch — {$fetched} new, {$duplicates} duplicates.");

        return self::SUCCESS;
    }
}