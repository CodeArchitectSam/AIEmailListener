<?php

namespace Tests\Feature\Maintenance;

use App\AI\AnalysisResult;
use App\AI\EmailAnalyzer;
use App\Enums\EmailStatus;
use App\Jobs\AnalyzeEmailJob;
use App\Mail\TaskAcknowledgement;
use App\Models\Email;
use App\Models\MaintenanceTask;
use App\Models\TaskLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class TaskCreationTest extends TestCase
{
    use RefreshDatabase;

    private function makeEmail(array $overrides = []): Email
    {
        return Email::factory()->create(array_merge([
            'sender_name' => 'Ali Hassan',
            'sender_email' => 'ali.hassan@company.com',
            'subject' => 'Water leaking from ceiling - Tower A, 5th floor pantry',
        ], $overrides));
    }

    private function result(array $overrides = []): AnalysisResult
    {
        return AnalysisResult::fromArray(array_merge([
            'is_maintenance_request' => true,
            'title' => 'Water leaking from ceiling',
            'description' => 'Water leaking into the pantry.',
            'category' => 'Plumbing',
            'priority' => 'High',
            'location' => ['building' => 'Tower A', 'floor' => '5th floor', 'room' => 'pantry'],
            'asset' => null,
            'confidence' => 0.9,
        ], $overrides));
    }

    private function bindAnalyzer(callable $resolver): void
    {
        $mock = Mockery::mock(EmailAnalyzer::class);
        $mock->shouldReceive('analyze')->andReturnUsing($resolver);
        $this->app->instance(EmailAnalyzer::class, $mock);
    }

    public function test_a_maintenance_email_creates_a_task_with_correct_attributes(): void
    {
        $this->bindAnalyzer(fn () => $this->result());

        $email = $this->makeEmail();

        AnalyzeEmailJob::dispatchSync($email);

        $task = MaintenanceTask::query()->first();

        expect($task)->not->toBeNull()
            ->and($task->task_number)->toMatch('/^MT-\d{6}$/')
            ->and($task->category)->toBe('Plumbing')
            ->and($task->priority)->toBe('Emergency') // water leak => forced Emergency
            ->and($task->status)->toBe('Open')
            ->and($task->location_building)->toBe('Tower A')
            ->and($task->location_floor)->toBe('5th floor')
            ->and($task->requester_email)->toBe('ali.hassan@company.com');

        expect($email->fresh()->status)->toBe(EmailStatus::Analyzed->value)
            ->and($email->fresh()->task_id)->toBe($task->id);

        $this->assertDatabaseHas('task_logs', [
            'task_id' => $task->id,
            'type' => 'first_report',
        ]);
    }

    public function test_a_non_maintenance_email_is_ignored_and_creates_no_task(): void
    {
        $this->bindAnalyzer(fn () => AnalysisResult::fromArray([
            'is_maintenance_request' => false,
        ]));

        $email = $this->makeEmail(['subject' => 'Monthly newsletter - October']);

        AnalyzeEmailJob::dispatchSync($email);

        expect(MaintenanceTask::count())->toBe(0)
            ->and($email->fresh()->status)->toBe(EmailStatus::Ignored->value);
    }

    public function test_an_email_with_low_confidence_creates_a_needs_review_task(): void
    {
        $this->bindAnalyzer(fn () => $this->result(['confidence' => 0.4]));

        $email = $this->makeEmail();

        AnalyzeEmailJob::dispatchSync($email);

        expect(MaintenanceTask::query()->first()->status)->toBe('Needs Review');
    }

    public function test_a_duplicate_report_from_another_person_only_logs_on_the_existing_task(): void
    {
        $this->bindAnalyzer(fn (Email $email) => $email->subject === 'Pantry ceiling leak on 5th floor Tower A'
            ? $this->result(['title' => 'Pantry ceiling leak on 5th floor Tower A', 'confidence' => 0.88])
            : $this->result());

        $first = $this->makeEmail();
        AnalyzeEmailJob::dispatchSync($first);
        $task = MaintenanceTask::query()->first();

        $second = $this->makeEmail([
            'message_id' => '<pantry-leak-2@company.com>',
            'sender_name' => 'Maryam Khan',
            'sender_email' => 'maryam.khan@company.com',
            'subject' => 'Pantry ceiling leak on 5th floor Tower A',
        ]);

        AnalyzeEmailJob::dispatchSync($second);

        expect(MaintenanceTask::count())->toBe(1)
            ->and($second->fresh()->task_id)->toBe($task->id)
            ->and($second->fresh()->status)->toBe(EmailStatus::Analyzed->value);

        $this->assertDatabaseHas('task_logs', [
            'task_id' => $task->id,
            'type' => 'duplicate',
            'message' => 'Duplicate report from maryam.khan@company.com',
        ]);
    }

    public function test_a_reply_is_logged_on_the_parent_thread_without_creating_a_new_task(): void
    {
        $this->bindAnalyzer(fn () => $this->result());

        $first = $this->makeEmail();
        AnalyzeEmailJob::dispatchSync($first);
        $task = MaintenanceTask::query()->first();

        $reply = $this->makeEmail([
            'message_id' => '<re-wm-1@company.com>',
            'in_reply_to' => $first->message_id,
            'subject' => 'RE: Water leaking from ceiling - Tower A, 5th floor pantry',
        ]);

        AnalyzeEmailJob::dispatchSync($reply);

        expect(MaintenanceTask::count())->toBe(1)
            ->and($reply->fresh()->task_id)->toBe($task->id);

        $this->assertDatabaseHas('task_logs', [
            'task_id' => $task->id,
            'type' => 'reply',
        ]);
    }

    public function test_an_acknowledgement_email_is_queued_for_a_new_task(): void
    {
        Mail::fake();

        $this->bindAnalyzer(fn () => $this->result());

        $email = $this->makeEmail();

        AnalyzeEmailJob::dispatchSync($email);

        Mail::assertQueued(TaskAcknowledgement::class, function (TaskAcknowledgement $mailable) {
            return $mailable->hasTo('ali.hassan@company.com')
                && $mailable->task->task_number !== null;
        });
    }

    public function test_an_email_is_marked_failed_when_analysis_keeps_failing(): void
    {
        $email = $this->makeEmail();

        $job = new AnalyzeEmailJob($email);
        $job->failed(new \RuntimeException('AI provider is down'));

        expect($email->fresh()->status)->toBe(EmailStatus::Failed->value)
            ->and($email->fresh()->analysis_error)->toContain('AI provider is down');
    }

    public function test_activity_log_contains_first_report_and_duplicate_entries_in_order(): void
    {
        $this->bindAnalyzer(fn () => $this->result());

        $first = $this->makeEmail();
        AnalyzeEmailJob::dispatchSync($first);
        $task = MaintenanceTask::query()->first();

        $this->makeEmail([
            'message_id' => '<pantry-leak-3@company.com>',
            'sender_name' => 'Someone Else',
            'sender_email' => 'someone@company.com',
            'subject' => 'Pantry ceiling leak on 5th floor Tower A',
        ])->tap(fn (Email $email) => AnalyzeEmailJob::dispatchSync($email));

        expect(TaskLog::where('task_id', $task->id)->get()->pluck('type')->all())
            ->toBe(['duplicate', 'first_report']);
    }
}