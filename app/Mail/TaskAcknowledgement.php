<?php

namespace App\Mail;

use App\Models\MaintenanceTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TaskAcknowledgement extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public MaintenanceTask $task)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Maintenance request received - {$this->task->task_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.acknowledgement',
            with: [
                'task' => $this->task,
                'severity' => $this->task->confidence < config('ai.confidence_threshold', 0.7)
                    ? 'needs-review'
                    : 'confirmed',
            ],
        );
    }
}