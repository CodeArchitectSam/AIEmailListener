<?php

namespace App\Http\Controllers\Api;

use App\Enums\Category;
use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\MaintenanceTask;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $query = MaintenanceTask::query()->withCount('emails')->with('sourceEmail');

        if ($request->filled('status')) {
            $query->where('status', TaskStatus::tryFromCaseInsensitive($request->status)?->value ?? $request->input('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', Priority::tryFromCaseInsensitive($request->priority)?->value ?? $request->input('priority'));
        }

        if ($request->filled('category')) {
            $query->where('category', Category::tryFromCaseInsensitive($request->category)?->value ?? $request->input('category'));
        }

        $tasks = $query->orderByDesc('created_at')->paginate($request->integer('per_page', 15));

        return TaskResource::collection($tasks);
    }

    public function show(MaintenanceTask $task): TaskResource
    {
        $task->load(['sourceEmail', 'emails', 'logs.email']);

        return new TaskResource($task);
    }

    public function update(UpdateTaskRequest $request, MaintenanceTask $task): TaskResource
    {
        $data = $request->validated();

        $task->update($data);

        $task->load(['sourceEmail', 'emails', 'logs.email']);

        return new TaskResource($task);
    }
}