<?php

namespace Tests\Feature\Maintenance;

use App\Jobs\AnalyzeEmailJob;
use App\Models\Email;
use App\Models\FakeMailboxEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EmailsFetchCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_command_fetches_new_emails_and_dispatches_a_job_per_email(): void
    {
        Queue::fake();

        FakeMailboxEmail::create([
            'message_id' => '<one@company.com>',
            'sender_email' => 'ali.hassan@company.com',
            'subject' => 'Water leaking from ceiling',
            'body_text' => 'Water leak in the pantry.',
            'attachments' => ['photo.jpg'],
            'received_at' => now(),
        ]);

        FakeMailboxEmail::create([
            'message_id' => '<two@company.com>',
            'sender_email' => 'john@company.com',
            'subject' => 'AC too warm in meeting room 304',
            'body_text' => 'The AC is warm.',
            'attachments' => null,
            'received_at' => now(),
        ]);

        $this->artisan('emails:fetch')->assertSuccessful();

        expect(Email::count())->toBe(2)
            ->and(Email::query()->where('message_id', '<one@company.com>')->first()->attachments)->toBe(['photo.jpg']);

        Queue::assertPushed(AnalyzeEmailJob::class, 2);
    }

    public function test_the_command_skips_duplicate_message_ids(): void
    {
        Queue::fake();

        $mailboxEmail = FakeMailboxEmail::create([
            'message_id' => '<one@company.com>',
            'sender_email' => 'ali.hassan@company.com',
            'subject' => 'Water leaking',
            'body_text' => 'Leak.',
            'received_at' => now(),
        ]);

        Email::create([
            'message_id' => '<one@company.com>',
            'sender_email' => 'ali.hassan@company.com',
            'subject' => 'Water leaking',
            'body_text' => 'Leak.',
            'received_at' => now(),
        ]);

        $mailboxEmail->update(['consumed_at' => null]); // pretend it is still unread

        $this->artisan('emails:fetch')->assertSuccessful();

        expect(Email::count())->toBe(1);

        Queue::assertNothingPushed();
    }
}