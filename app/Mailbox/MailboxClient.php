<?php

namespace App\Mailbox;

use Illuminate\Support\Collection;

interface MailboxClient
{
    /**
     * Fetch emails currently unread in the mailbox.
     * The implementation is responsible for marking them as read/consumed.
     *
     * @return Collection<int, IncomingEmail>
     */
    public function fetchUnreadEmails(): Collection;
}