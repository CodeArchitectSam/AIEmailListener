<?php

namespace Tests\Feature\Maintenance;

use App\Enums\Category;
use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Jobs\AnalyzeEmailJob;
use App\Models\Email;
use App\Models\MaintenanceTask;
use App\Models\TaskLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeTask(array $overrides = []): MaintenanceTask
    {
        return MaintenanceTask::factory()->create(array_merge([
            'task_number' => 'MT-000001',
            'title' => 'Water leaking from ceiling',
            'category' => 'Plumbing',
            'priority' => 'Emergency',
            'status' => TaskStatus::Open->value,
            'location_building' => 'Tower A',
            'location_floor' => '5th floor',
            'location_room' => 'pantry',
        ], $overrides));
    }

    public function test_the_task_list_is_paginated_and_filterable(): void
    {
        $this->makeTask(['task_number' => 'MT-000001', 'category' => 'Plumbing', 'priority' => 'Emergency']);
        $this->makeTask(['task_number' => 'MT-000002', 'category' => 'HVAC', 'priority' => 'High']);
        $this->makeTask(['task_number' => 'MT-000003', 'category' => 'Electrical', 'priority' => 'Medium']);

        $response = $this->getJson('/api/tasks?category=HVAC&priority=High');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.task_number', 'MT-000002');

        $this->getJson('/api/tasks?priority=Emergency')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.category', 'Plumbing');
    }

    public function test_a_task_detail_includes_source_email_and_logs(): void
    {
        $email = Email::factory()->create(['status' => 'analyzed']);
        $task = $this->makeTask(['source_email_id' => $email->id]);
        $email->update(['task_id' => $task->id]);

        TaskLog::create([
            'task_id' => $task->id,
            'email_id' => $email->id,
            'type' => 'first_report',
            'message' => "First report on {$task->task_number}",
        ]);

        $response = $this->getJson("/api/tasks/{$task->id}");

        $response->assertOk()
            ->assertJsonPath('data.task_number', 'MT-000001')
            ->assertJsonPath('data.source_email.id', $email->id)
            ->assertJsonCount(1, 'data.logs')
            ->assertJsonPath('data.logs.0.type', 'first_report');
    }

    public function test_a_task_can_be_updated(): void
    {
        $task = $this->makeTask();

        $response = $this->patchJson("/api/tasks/{$task->id}", [
            'status' => TaskStatus::InProgress->value,
            'priority' => Priority::High->value,
            'category' => Category::Civil->value,
            'location_building' => 'Tower B',
            'title' => 'Updated title',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'In Progress')
            ->assertJsonPath('data.priority', 'High')
            ->assertJsonPath('data.category', 'Civil')
            ->assertJsonPath('data.location.building', 'Tower B')
            ->assertJsonPath('data.title', 'Updated title');

        $this->assertDatabaseHas('maintenance_tasks', [
            'id' => $task->id,
            'status' => 'In Progress',
            'location_building' => 'Tower B',
        ]);
    }

    public function test_a_task_update_rejects_invalid_values(): void
    {
        $task = $this->makeTask();

        $this->patchJson("/api/tasks/{$task->id}", ['status' => 'NotAStatus'])
            ->assertStatus(422);
    }

    public function test_reprocessing_an_email_resets_it_and_queues_analysis(): void
    {
        Queue::fake();

        $email = Email::factory()->create(['status' => 'failed', 'analysis_error' => 'boom']);

        $response = $this->postJson("/api/emails/{$email->id}/reprocess");

        $response->assertStatus(202)
            ->assertJsonPath('email.status', 'pending');

        Queue::assertPushed(AnalyzeEmailJob::class, function (AnalyzeEmailJob $job) use ($email) {
            return $job->email->is($email);
        });

        $this->assertDatabaseHas('emails', [
            'id' => $email->id,
            'status' => 'pending',
            'analysis_error' => null,
        ]);
    }
}