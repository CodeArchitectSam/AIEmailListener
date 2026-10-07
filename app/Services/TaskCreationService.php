<?php

namespace App\Services;

use App\AI\AnalysisResult;
use App\AI\Location;
use App\Enums\TaskStatus;
use App\Mail\TaskAcknowledgement;
use App\Models\Email;
use App\Models\MaintenanceTask;
use App\Models\TaskLog;
use Illuminate\Support\Facades\Mail;

/**
 * Turns an analyzed email into the right outcome:
 * ignored, appended to an existing thread, linked as a duplicate, or a new task.
 */
class TaskCreationService
{
    /**
     * @return MaintenanceTask|null  the created/existing task, or null when ignored
     */
    public function process(Email $email, AnalysisResult $result): ?MaintenanceTask
    {
        $email->analysis = $result->toArray();
        $email->save();

        if (! $result->isMaintenanceRequest) {
            $email->markIgnored($email->analysis);

            return null;
        }

        if ($email->in_reply_to !== null && ($replyTask = $this->findTaskForThread($email)) !== null) {
            $this->linkEmailAndLog($replyTask, $email, 'reply', "Reply on {$replyTask->task_number}");

            return $replyTask;
        }

        if (($duplicateTask = $this->findOpenDuplicate($result)) !== null) {
            $this->linkEmailAndLog($duplicateTask, $email, 'duplicate', "Duplicate report from {$email->sender_email}");

            return $duplicateTask;
        }

        return $this->createTaskFromAnalysis($email, $result);
    }

    private function findTaskForThread(Email $email): ?MaintenanceTask
    {
        $parent = Email::query()->where('message_id', $email->in_reply_to)->first();

        if ($parent === null || $parent->task_id === null) {
            return null;
        }

        return $parent->task;
    }

    /**
     * Same location + same category + similar issue on an open task.
     */
    private function findOpenDuplicate(AnalysisResult $result): ?MaintenanceTask
    {
        $candidates = MaintenanceTask::query()
            ->whereIn('status', [TaskStatus::Open->value, TaskStatus::NeedsReview->value])
            ->where('category', $result->category->value)
            ->get();

        foreach ($candidates as $task) {
            $taskLocation = new Location(
                building: $task->location_building,
                floor: $task->location_floor,
                room: $task->location_room,
            );

            if (! $taskLocation->equals($result->location)) {
                continue;
            }

            $existingText = $task->title.($task->description ? " {$task->description}" : '');
            $incomingText = $result->title.($result->description ? " {$result->description}" : '');

            if ($this->similarIssue($existingText, $incomingText)) {
                return $task;
            }
        }

        return null;
    }

    private function similarIssue(string $existingText, string $incomingText): bool
    {
        $existing = $this->tokenSet($existingText);
        $incoming = $this->tokenSet($incomingText);

        if ($existing === [] || $incoming === []) {
            return false;
        }

        $intersection = count(array_intersect($existing, $incoming));
        $overlap = $intersection / min(count($existing), count($incoming));

        return $overlap >= 0.5;
    }

    /**
     * @return list<string>
     */
    private function tokenSet(string $text): array
    {
        $stopWords = [
            'a', 'an', 'the', 'in', 'of', 'to', 'on', 'for', 'and', 'from', 'at',
            'still', 'not', 'is', 'are', 'was', 'were', 'it', 'there', 'into',
            'about', 'get', 'got', 'please', 'hi', 'hello', 'team', 'thanks',
        ];

        $words = preg_split('/[^a-z0-9]+/', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $tokens = [];

        foreach ($words as $word) {
            if (in_array($word, $stopWords, true)) {
                continue;
            }

            $tokens[] = $this->lightStem($word);
        }

        return array_values(array_unique($tokens));
    }

    private function lightStem(string $word): string
    {
        if (strlen($word) > 3 && str_ends_with($word, 'ing')) {
            return substr($word, 0, -3);
        }

        if (strlen($word) > 3 && str_ends_with($word, 'ed')) {
            return substr($word, 0, -2);
        }

        if (strlen($word) > 2 && str_ends_with($word, 'es')) {
            return substr($word, 0, -2);
        }

        if (strlen($word) > 3 && str_ends_with($word, 's')) {
            return substr($word, 0, -1);
        }

        return $word;
    }

    private function createTaskFromAnalysis(Email $email, AnalysisResult $result): MaintenanceTask
    {
        $status = $result->confidence < config('ai.confidence_threshold', 0.7)
            ? TaskStatus::NeedsReview->value
            : TaskStatus::Open->value;

        $task = MaintenanceTask::create([
            'title' => $result->title,
            'description' => $result->description,
            'category' => $result->category->value,
            'priority' => $result->priority->value,
            'status' => $status,
            'location_building' => $result->location->building,
            'location_floor' => $result->location->floor,
            'location_room' => $result->location->room,
            'asset' => $result->asset,
            'confidence' => $result->confidence,
            'requester_name' => $email->sender_name,
            'requester_email' => $email->sender_email,
            'source_email_id' => $email->id,
            'sla_due_at' => now()->addHours($result->priority->slaHours()),
        ]);

        $task->assignTaskNumber();

        $this->linkEmailAndLog($task, $email, 'first_report', "First report on {$task->task_number}");

        $this->acknowledge($task);

        return $task;
    }

    private function linkEmailAndLog(MaintenanceTask $task, Email $email, string $type, string $message): void
    {
        $email->markAnalyzed($email->analysis ?? [], $task->id);

        TaskLog::create([
            'task_id' => $task->id,
            'email_id' => $email->id,
            'type' => $type,
            'message' => $message,
        ]);
    }

    private function acknowledge(MaintenanceTask $task): void
    {
        if ($task->requester_email !== null && $task->requester_email !== '') {
            Mail::to($task->requester_email)
                ->queue(new TaskAcknowledgement($task));
        }
    }
}