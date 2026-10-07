<?php

namespace Database\Factories;

use App\Enums\EmailStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Email>
 */
class EmailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $from = $this->faker->name();

        return [
            'message_id' => '<'.Str::uuid().'@'.$this->faker->domainName().'>',
            'in_reply_to' => null,
            'sender_name' => $from,
            'sender_email' => Str::kebab($from).'@'.$this->faker->domainName(),
            'subject' => $this->faker->sentence(6),
            'body_text' => $this->faker->paragraph(),
            'attachments' => [],
            'received_at' => now(),
            'status' => EmailStatus::Pending->value,
            'attempts' => 0,
            'task_id' => null,
            'analysis' => null,
            'analysis_error' => null,
        ];
    }
}