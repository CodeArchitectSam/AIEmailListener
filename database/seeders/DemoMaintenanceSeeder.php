<?php

namespace Database\Seeders;

use App\Enums\TaskStatus;
use App\Models\Email;
use App\Models\MaintenanceTask;
use App\Models\TaskLog;
use Illuminate\Database\Seeder;

/**
 * Seeds one already-processed maintenance task so the Vue list and detail
 * page have content immediately, before the mailbox has been consumed.
 */
class DemoMaintenanceSeeder extends Seeder
{
    public function run(): void
    {
        $email = Email::create([
            'message_id' => '<demo-pantry-leak-@company.com>',
            'in_reply_to' => null,
            'sender_name' => 'Ali Hassan',
            'sender_email' => 'ali.hassan@company.com',
            'subject' => 'Water leaking from ceiling - Tower A, 5th floor pantry',
            'body_text' => "Demo source email: water leaking from the ceiling in the 5th floor pantry of Tower A.",
            'attachments' => [],
            'received_at' => now()->subDay(),
            'status' => 'analyzed',
            'attempts' => 1,
            'analysis' => [
                'is_maintenance_request' => true,
                'title' => 'Water leaking from ceiling - Tower A, 5th floor pantry',
                'description' => 'Water leaking from the ceiling in the 5th floor pantry of Tower A.',
                'category' => 'Plumbing',
                'priority' => 'Emergency',
                'location' => ['building' => 'Tower A', 'floor' => '5th floor', 'room' => 'pantry'],
                'asset' => null,
                'confidence' => 0.95,
            ],
        ]);

        $task = MaintenanceTask::create([
            'title' => 'Water leaking from ceiling - Tower A, 5th floor pantry',
            'description' => 'Water leaking from the ceiling in the 5th floor pantry of Tower A.',
            'category' => 'Plumbing',
            'priority' => 'Emergency',
            'status' => TaskStatus::Open->value,
            'location_building' => 'Tower A',
            'location_floor' => '5th floor',
            'location_room' => 'pantry',
            'confidence' => 0.95,
            'requester_name' => 'Ali Hassan',
            'requester_email' => 'ali.hassan@company.com',
            'source_email_id' => $email->id,
            'sla_due_at' => now()->addHours(2),
        ]);

        $task->assignTaskNumber();

        $email->update(['task_id' => $task->id]);

        TaskLog::create([
            'task_id' => $task->id,
            'email_id' => $email->id,
            'type' => 'first_report',
            'message' => "First report on {$task->task_number}",
        ]);
    }
}