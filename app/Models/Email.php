<?php

namespace App\Models;

use App\Enums\EmailStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Email extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'in_reply_to',
        'sender_name',
        'sender_email',
        'subject',
        'body_text',
        'attachments',
        'received_at',
        'status',
        'attempts',
        'task_id',
        'analysis',
        'analysis_error',
    ];

    protected $casts = [
        'attachments' => 'array',
        'received_at' => 'datetime',
        'analysis' => 'array',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(MaintenanceTask::class, 'task_id');
    }

    public function markProcessing(): void
    {
        $this->update([
            'status' => EmailStatus::Processing->value,
            'attempts' => $this->attempts + 1,
        ]);
    }

    public function markAnalyzed(array $analysis, ?int $taskId = null): void
    {
        $this->update([
            'status' => EmailStatus::Analyzed->value,
            'analysis' => $analysis,
            'analysis_error' => null,
            'task_id' => $taskId ?? $this->task_id,
        ]);
    }

    public function markIgnored(array $analysis): void
    {
        $this->update([
            'status' => EmailStatus::Ignored->value,
            'analysis' => $analysis,
            'analysis_error' => null,
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'status' => EmailStatus::Failed->value,
            'analysis_error' => $error,
        ]);
    }

    public function resetForReprocessing(): void
    {
        $this->update([
            'status' => EmailStatus::Pending->value,
            'attempts' => 0,
            'analysis' => null,
            'analysis_error' => null,
            'task_id' => null,
        ]);
    }

    public function hasBeenProcessed(): bool
    {
        return in_array($this->status, [
            EmailStatus::Analyzed->value,
            EmailStatus::Ignored->value,
            EmailStatus::Failed->value,
        ], true);
    }
}