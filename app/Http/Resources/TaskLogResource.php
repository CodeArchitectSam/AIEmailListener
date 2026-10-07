<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskLogResource extends JsonResource
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
            'type' => $this->type,
            'message' => $this->message,
            'sender' => [
                'name' => $this->senderNameLabel(),
                'email' => $this->email?->sender_email,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    private function senderNameLabel(): ?string
    {
        return $this->email?->sender_name
            ?? ($this->type === 'first_report' ? 'Original requester' : null);
    }
}