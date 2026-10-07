<?php

namespace App\Http\Controllers;

use App\Enums\Category;
use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Models\MaintenanceTask;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(Request $request): Response
    {
        $query = MaintenanceTask::query()->withCount('emails');

        foreach (['status' => TaskStatus::class, 'priority' => Priority::class, 'category' => Category::class] as $field => $enum) {
            if ($request->filled($field)) {
                $query->where($field, $enum::tryFromCaseInsensitive($request->input($field))?->value ?? $request->input($field));
            }
        }

        $tasks = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        return Inertia::render('tasks/Index', [
            'tasks' => [
                'data' => $tasks->map(fn (MaintenanceTask $task): array => $this->serializeTask($task))->values()->all(),
                'meta' => [
                    'current_page' => $tasks->currentPage(),
                    'last_page' => $tasks->lastPage(),
                    'total' => $tasks->total(),
                    'per_page' => $tasks->perPage(),
                    'links' => $tasks->getUrlRange(1, $tasks->lastPage()),
                ],
            ],
            'filters' => $request->only(['status', 'priority', 'category']),
            'options' => [
                'statuses' => TaskStatus::values(),
                'priorities' => Priority::values(),
                'categories' => Category::values(),
            ],
        ]);
    }

    public function show(MaintenanceTask $task): Response
    {
        $task->load(['sourceEmail', 'emails', 'logs.email']);

        return Inertia::render('tasks/Show', [
            'task' => [
                'id' => $task->id,
                'task_number' => $task->task_number,
                'title' => $task->title,
                'description' => $task->description,
                'category' => $task->category,
                'priority' => $task->priority,
                'status' => $task->status,
                'location' => $this->serializeLocation($task),
                'asset' => $task->asset,
                'confidence' => $task->confidence,
                'requester' => [
                    'name' => $task->requester_name,
                    'email' => $task->requester_email,
                ],
                'sla_due_at' => $task->sla_due_at?->toIso8601String(),
                'created_at' => $task->created_at?->toIso8601String(),
                'source_email' => $task->sourceEmail ? $this->serializeEmail($task->sourceEmail) : null,
                'emails' => $task->emails->map(fn ($email) => $this->serializeEmail($email))->values(),
                'logs' => $task->logs->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'type' => $log->type,
                        'message' => $log->message,
                        'sender' => $log->email?->sender_name,
                        'sender_email' => $log->email?->sender_email,
                        'created_at' => $log->created_at?->toIso8601String(),
                    ];
                })->values(),
            ],
            'options' => [
                'statuses' => TaskStatus::values(),
                'priorities' => Priority::values(),
                'categories' => Category::values(),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeTask(MaintenanceTask $task): array
    {
        return [
            'id' => $task->id,
            'task_number' => $task->task_number,
            'title' => $task->title,
            'category' => $task->category,
            'priority' => $task->priority,
            'status' => $task->status,
            'location' => $this->serializeLocation($task),
            'asset' => $task->asset,
            'confidence' => $task->confidence,
            'requester' => [
                'name' => $task->requester_name,
                'email' => $task->requester_email,
            ],
            'emails_count' => $task->emails_count,
            'sla_due_at' => $task->sla_due_at?->toIso8601String(),
            'created_at' => $task->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array{building: string|null, floor: string|null, room: string|null}
     */
    private function serializeLocation(MaintenanceTask $task): array
    {
        return [
            'building' => $task->location_building,
            'floor' => $task->location_floor,
            'room' => $task->location_room,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeEmail(object $email): array
    {
        return [
            'id' => $email->id,
            'message_id' => $email->message_id,
            'in_reply_to' => $email->in_reply_to,
            'sender_name' => $email->sender_name,
            'sender_email' => $email->sender_email,
            'subject' => $email->subject,
            'body_text' => $email->body_text,
            'attachments' => $email->attachments ?? [],
            'received_at' => $email->received_at?->toIso8601String(),
            'status' => $email->status,
            'analysis' => $email->analysis,
        ];
    }
}