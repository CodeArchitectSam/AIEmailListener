<?php

namespace App\AI;

use App\AI\Exceptions\InvalidAiResponseException;
use App\Enums\Category;
use App\Enums\Priority;

/**
 * Validated, normalized result of the AI analysis. Immutable after creation.
 */
final readonly class AnalysisResult
{
    /**
     * Build an AnalysisResult from a raw AI provider payload. Applies the
     * mandatory priority override rules and clamps confidence. Throws
     * InvalidAiResponseException on schema violations.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromAiResponse(array $data, string $sourceText): self
    {
        $isMaintenance = self::isMaintenanceFrom($data);

        if (! $isMaintenance) {
            return new self(
                isMaintenanceRequest: false,
                title: 'Not a maintenance request',
                description: null,
                category: Category::Other,
                priority: Priority::Low,
                location: new Location(null, null, null),
                asset: null,
                confidence: 0.0,
            );
        }

        return new self(
            isMaintenanceRequest: true,
            title: mb_strimwidth(trim((string) (self::requiredString($data, 'title'))), 0, 80),
            description: self::nullableString($data['description'] ?? null),
            category: self::categoryFrom($data),
            priority: self::priorityFrom($data, $sourceText),
            location: Location::fromArray($data['location'] ?? null),
            asset: self::nullableString($data['asset'] ?? null),
            confidence: self::confidenceFrom($data),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return self::fromAiResponse($data, $data['subject'] ?? '');
    }

    public function __construct(
        public readonly bool $isMaintenanceRequest,
        public readonly string $title,
        public readonly ?string $description,
        public readonly Category $category,
        public readonly Priority $priority,
        public readonly Location $location,
        public readonly ?string $asset,
        public readonly float $confidence,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'is_maintenance_request' => $this->isMaintenanceRequest,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category->value,
            'priority' => $this->priority->value,
            'location' => $this->location->toArray(),
            'asset' => $this->asset,
            'confidence' => $this->confidence,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function isMaintenanceFrom(array $data): bool
    {
        if (! array_key_exists('is_maintenance_request', $data)) {
            throw InvalidAiResponseException::forField('is_maintenance_request');
        }

        $value = filter_var($data['is_maintenance_request'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($value === null) {
            throw InvalidAiResponseException::forField('is_maintenance_request');
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function requiredString(array $data, string $field): string
    {
        if (! isset($data[$field]) || ! is_string($data[$field]) || trim($data[$field]) === '') {
            throw InvalidAiResponseException::forField($field, 'required string');
        }

        return $data[$field];
    }

    private static function nullableString(mixed $value): ?string
    {
        if (is_string($value) || is_numeric($value)) {
            $value = trim((string) $value);

            return $value === '' ? null : $value;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function categoryFrom(array $data): Category
    {
        if (! isset($data['category']) || ! is_string($data['category'])) {
            throw InvalidAiResponseException::forField('category');
        }

        return Category::tryFromCaseInsensitive($data['category'])
            ?? throw InvalidAiResponseException::forField('category', "unknown value [{$data['category']}]");
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function priorityFrom(array $data, string $sourceText): Priority
    {
        if (! isset($data['priority']) || ! is_string($data['priority'])) {
            throw InvalidAiResponseException::forField('priority');
        }

        $priority = Priority::tryFromCaseInsensitive($data['priority']);

        if ($priority === null) {
            throw InvalidAiResponseException::forField('priority', "unknown value [{$data['priority']}]");
        }

        return self::shouldBeEmergency($sourceText) ? Priority::Emergency : $priority;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function confidenceFrom(array $data): float
    {
        $confidence = $data['confidence'] ?? 0.5;

        if (! is_numeric($confidence)) {
            throw InvalidAiResponseException::forField('confidence');
        }

        return max(0.0, min(1.0, (float) $confidence));
    }

    /**
     * Business rule: these situations are always Emergency regardless of what the AI guessed.
     */
    public static function shouldBeEmergency(string $sourceText): bool
    {
        $text = mb_strtolower($sourceText);

        $triggers = [
            'water leak',
            'leaking',
            'fire alarm',
            'power outage',
            'no power',
            'trapped in lift',
            'trapped in elevator',
            'stuck in lift',
            'stuck in elevator',
        ];

        foreach ($triggers as $trigger) {
            if (str_contains($text, $trigger)) {
                return true;
            }
        }

        return false;
    }
}