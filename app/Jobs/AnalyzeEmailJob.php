<?php

namespace App\Jobs;

use App\AI\EmailAnalyzer;
use App\AI\Exceptions\AiServiceException;
use App\AI\Exceptions\InvalidAiResponseException;
use App\Enums\EmailStatus;
use App\Models\Email;
use App\Services\TaskCreationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class AnalyzeEmailJob implements ShouldQueue
{
    use Queueable;

    /**
     * One initial attempt plus up to three retries.
     */
    public int $tries = 4;

    /**
     * @var array<int, int> seconds to wait before each retry
     */
    public array $backoff = [10, 30, 60];

    public function __construct(public Email $email)
    {
    }

    public function handle(EmailAnalyzer $analyzer, TaskCreationService $service): void
    {
        if ($this->email->status === EmailStatus::Failed->value) {
            return;
        }

        $this->email->markProcessing();

        $result = $analyzer->analyze($this->email);

        $service->process($this->email, $result);
    }

    /**
     * Let the queue worker retry transient failures; only persist a terminal
     * "failed" state once attempts are exhausted.
     */
    public function failed(?Throwable $e): void
    {
        if ($this->email->exists) {
            $this->email->markFailed($e?->getMessage() ?? 'Unknown AI analysis error');
        }
    }
}