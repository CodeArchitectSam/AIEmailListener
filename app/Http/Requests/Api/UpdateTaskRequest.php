<?php

namespace App\Http\Requests\Api;

use App\Enums\Category;
use App\Enums\Priority;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:80'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'string', 'in:'.implode(',', TaskStatus::values())],
            'priority' => ['sometimes', 'string', 'in:'.implode(',', Priority::values())],
            'category' => ['sometimes', 'string', 'in:'.implode(',', Category::values())],
            'location_building' => ['sometimes', 'nullable', 'string', 'max:120'],
            'location_floor' => ['sometimes', 'nullable', 'string', 'max:120'],
            'location_room' => ['sometimes', 'nullable', 'string', 'max:120'],
            'asset' => ['sometimes', 'nullable', 'string', 'max:120'],
            'confidence' => ['sometimes', 'nullable', 'numeric', 'between:0,1'],
        ];
    }
}