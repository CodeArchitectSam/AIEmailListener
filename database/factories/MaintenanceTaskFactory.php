<?php

namespace Database\Factories;

use App\Enums\Category;
use App\Enums\Priority;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MaintenanceTask>
 */
class MaintenanceTaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_number' => 'MT-'.str_pad((string) Str::random(6, '1234567890'), 6, '0', STR_PAD_LEFT),
            'title' => $this->faker->sentence(6),
            'description' => $this->faker->paragraph(),
            'category' => $this->faker->randomElement(Category::values()),
            'priority' => $this->faker->randomElement(Priority::values()),
            'status' => TaskStatus::Open->value,
            'location_building' => 'Tower A',
            'location_floor' => '5th Floor',
            'location_room' => null,
            'asset' => null,
            'confidence' => 0.9,
            'requester_name' => $this->faker->name(),
            'requester_email' => $this->faker->safeEmail(),
            'source_email_id' => null,
            'sla_due_at' => now()->addHours(24),
        ];
    }
}