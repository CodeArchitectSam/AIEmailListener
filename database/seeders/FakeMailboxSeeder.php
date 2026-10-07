<?php

namespace Database\Seeders;

use App\Models\FakeMailboxEmail;
use Illuminate\Database\Seeder;

/**
 * Sample inbox used by the `fake` mailbox driver, mirroring the six cases
 * from the interview brief (new task, duplicate, HVAC, electrical, ignore,
 * reply). Let the pipeline run without any real IMAP account.
 */
class FakeMailboxSeeder extends Seeder
{
    public function run(): void
    {
        $base = now()->subMinutes(10);
        $waterLeakMessageId = '<water-leak-tower-a-5th-'.now()->timestamp.'@company.com>';

        $emails = [
            [
                'message_id' => $waterLeakMessageId,
                'in_reply_to' => null,
                'sender_name' => 'Ali Hassan',
                'sender_email' => 'ali.hassan@company.com',
                'subject' => 'Water leaking from ceiling - Tower A, 5th floor pantry',
                'body_text' => "Hi team,\n\nThere is water leaking from the ceiling in the pantry on the 5th floor of Tower A. It started about an hour ago and the floor is getting wet.\n\nRegards,\nAli",
                'attachments' => [],
                'received_at' => $base,
            ],
            [
                'message_id' => '<pantry-leak-dup-'.now()->timestamp.'@company.com>',
                'in_reply_to' => null,
                'sender_name' => 'Maryam Khan',
                'sender_email' => 'maryam.khan@company.com',
                'subject' => 'Pantry ceiling leak on 5th floor Tower A',
                'body_text' => "Hello,\n\nThe ceiling in the 5th floor pantry (Tower A) is leaking water all over the counter. Please send someone.\n\nMaryam",
                'attachments' => [],
                'received_at' => $base->addMinutes(2),
            ],
            [
                'message_id' => '<ac-meeting-room-304-'.now()->timestamp.'@company.com>',
                'in_reply_to' => null,
                'sender_name' => 'John Doe',
                'sender_email' => 'john.doe@company.com',
                'subject' => 'AC too warm in meeting room 304',
                'body_text' => "The AC in meeting room 304 is not cooling properly, it is noticeably warm in there today.\n\nThanks,\nJohn",
                'attachments' => [],
                'received_at' => $base->addMinutes(4),
            ],
            [
                'message_id' => '<light-parking-b2-'.now()->timestamp.'@company.com>',
                'in_reply_to' => null,
                'sender_name' => 'Sara Latif',
                'sender_email' => 'sara.latif@company.com',
                'subject' => 'Light flickering in parking B2 near pillar 14',
                'body_text' => "Hello,\n\nA light near pillar 14 in parking B2 is flickering on and off, it might go out soon.\n\nSara",
                'attachments' => [],
                'received_at' => $base->addMinutes(6),
            ],
            [
                'message_id' => '<oct-newsletter-'.now()->timestamp.'@company.com>',
                'in_reply_to' => null,
                'sender_name' => 'Marketing',
                'sender_email' => 'marketing@company.com',
                'subject' => 'Monthly newsletter - October',
                'body_text' => "Here is your monthly newsletter with company updates and events for October. No action needed.",
                'attachments' => [],
                'received_at' => $base->addMinutes(8),
            ],
            [
                'message_id' => '<re-mt-'.now()->timestamp.'@company.com>',
                'in_reply_to' => $waterLeakMessageId,
                'sender_name' => 'Ali Hassan',
                'sender_email' => 'ali.hassan@company.com',
                'subject' => 'RE: Water leaking from ceiling - Tower A, 5th floor pantry',
                'body_text' => "Hi, this is still not fixed, the water is leaking again from the same ceiling spot in the pantry.\n\nAli",
                'attachments' => [],
                'received_at' => $base->addMinutes(15),
            ],
        ];

        foreach ($emails as $email) {
            FakeMailboxEmail::updateOrCreate(['message_id' => $email['message_id']], $email);
        }
    }
}