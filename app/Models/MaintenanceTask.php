<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_number',
        'title',
        'description',
        'category',
        'priority',
        'status',
        'location_building',
        'location_floor',
        'location_room',
        'asset',
        'confidence',
        'requester_name',
        'requester_email',
        'source_email_id',
        'sla_due_at',
    ];

    protected $casts = [
        'confidence' => 'float',
        'sla_due_at' => 'datetime',
    ];

    public function sourceEmail(): BelongsTo
    {
        return $this->belongsTo(Email::class, 'source_email_id');
    }

    public function emails(): HasMany
    {
        return $this->hasMany(Email::class, 'task_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(TaskLog::class, 'task_id')->orderByDesc('created_at');
    }

    public function reportsCount(): int
    {
        return $this->emails()->count();
    }

    public function assignTaskNumber(): void
    {
        if ($this->task_number === null) {
            $this->forceFill(['task_number' => 'MT-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT)])->save();
        }
    }
}