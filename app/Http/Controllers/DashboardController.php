<?php

namespace App\Http\Controllers;

use App\Enums\Category;
use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Models\Email;
use App\Models\MaintenanceTask;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $openStatuses = array_map(
            fn (TaskStatus $status): string => $status->value,
            array_filter(TaskStatus::cases(), fn (TaskStatus $status): bool => $status->isOpen()),
        );

        $total = MaintenanceTask::count();

        return Inertia::render('Dashboard', [
            'stats' => [
                'total' => $total,
                'open' => MaintenanceTask::where('status', 'Open')->count(),
                'needs_review' => MaintenanceTask::where('status', TaskStatus::NeedsReview->value)->count(),
                'in_progress' => MaintenanceTask::where('status', TaskStatus::InProgress->value)->count(),
                'overdue' => MaintenanceTask::whereIn('status', $openStatuses)
                    ->whereNotNull('sla_due_at')
                    ->where('sla_due_at', '<', now())
                    ->count(),
            ],
            'by_status' => $this->countsBy(groupBy: 'status', order: TaskStatus::values()),
            'by_priority' => $this->countsBy(groupBy: 'priority', order: Priority::values()),
            'by_category' => $this->countsBy(groupBy: 'category', order: Category::values()),
            'recent_tasks' => MaintenanceTask::query()
                ->withCount('emails')
                ->latest('id')
                ->limit(6)
                ->get()
                ->map(fn (MaintenanceTask $task): array => [
                    'id' => $task->id,
                    'task_number' => $task->task_number,
                    'title' => $task->title,
                    'category' => $task->category,
                    'priority' => $task->priority,
                    'status' => $task->status,
                    'sla_due_at' => $task->sla_due_at?->toISOString(),
                    'emails_count' => $task->emails_count,
                ]),
            'recent_emails' => Email::query()
                ->latest('received_at')
                ->limit(6)
                ->get(['id', 'task_id', 'sender_name', 'sender_email', 'subject', 'status', 'received_at'])
                ->map(fn (Email $email): array => [
                    'id' => $email->id,
                    'task_id' => $email->task_id,
                    'sender_name' => $email->sender_name,
                    'sender_email' => $email->sender_email,
                    'subject' => $email->subject,
                    'status' => $email->status,
                    'received_at' => $email->received_at->toISOString(),
                ]),
        ]);
    }

    /**
     * Count tasks grouped by the given column, ordered to include zero counts.
     *
     * @param  list<string>  $order
     * @return list<array{key: string, count: int}>
     */
    private function countsBy(string $groupBy, array $order): array
    {
        $counts = MaintenanceTask::query()
            ->select($groupBy)
            ->selectRaw('count(*) as aggregate')
            ->groupBy($groupBy)
            ->pluck('aggregate', $groupBy);

        return collect($order)
            ->map(fn (string $key): array => [
                'key' => $key,
                'count' => (int) $counts->get($key, 0),
            ])
            ->values()
            ->all();
    }
}