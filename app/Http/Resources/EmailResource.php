<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmailResource extends JsonResource
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
            'message_id' => $this->message_id,
            'in_reply_to' => $this->in_reply_to,
            'sender' => [
                'name' => $this->sender_name,
                'email' => $this->sender_email,
            ],
            'subject' => $this->subject,
            'body_text' => $this->body_text,
            'attachments' => $this->attachments ?? [],
            'received_at' => $this->received_at?->toIso8601String(),
            'status' => $this->status,
            'analysis' => $this->analysis,
        ];
    }
}