<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FakeMailboxEmail extends Model
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
        'consumed_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'received_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];
}