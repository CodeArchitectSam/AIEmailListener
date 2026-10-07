<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'task_number' => $this->task_number,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'priority' => $this->priority,
            'status' => $this->status,
            'location' => [
                'building' => $this->location_building,
                'floor' => $this->location_floor,
                'room' => $this->location_room,
            ],
            'asset' => $this->asset,
            'confidence' => $this->confidence,
            'requester' => [
                'name' => $this->requester_name,
                'email' => $this->requester_email,
            ],
            'reports_count' => $this->whenCounted('emails'),
            'sla_due_at' => $this->sla_due_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'source_email' => $this->whenLoaded('sourceEmail', fn () => new EmailResource($this->sourceEmail)),
            'emails' => $this->whenLoaded('emails', fn () => EmailResource::collection($this->emails)),
            'logs' => $this->whenLoaded('logs', fn () => TaskLogResource::collection($this->logs)),
        ];
    }
}