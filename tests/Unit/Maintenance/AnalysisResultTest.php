<?php

namespace Tests\Unit\Maintenance;

use App\AI\AnalysisResult;
use App\AI\Exceptions\InvalidAiResponseException;
use App\Enums\Category;
use App\Enums\Priority;
use PHPUnit\Framework\TestCase;

class AnalysisResultTest extends TestCase
{
    public function test_it_parses_a_valid_ai_response(): void
    {
        $result = AnalysisResult::fromAiResponse([
            'is_maintenance_request' => true,
            'title' => 'AC not cooling in Tower B, Room 304',
            'description' => 'The AC is running but the room stays warm.',
            'category' => 'HVAC',
            'priority' => 'High',
            'location' => ['building' => 'Tower B', 'floor' => null, 'room' => 'Room 304'],
            'asset' => 'FCU-12',
            'confidence' => 0.92,
        ], 'AC not cooling in Tower B, Room 304');

        expect($result->isMaintenanceRequest)->toBeTrue()
            ->and($result->title)->toBe('AC not cooling in Tower B, Room 304')
            ->and($result->category)->toBe(Category::HVAC)
            ->and($result->priority)->toBe(Priority::High)
            ->and($result->location->building)->toBe('Tower B')
            ->and($result->asset)->toBe('FCU-12')
            ->and($result->confidence)->toBe(0.92);
    }

    public function test_a_non_maintenance_email_produces_a_lightweight_result(): void
    {
        $result = AnalysisResult::fromAiResponse([
            'is_maintenance_request' => false,
        ], 'Monthly newsletter - October');

        expect($result->isMaintenanceRequest)->toBeFalse()
            ->and($result->title)->toBe('Not a maintenance request')
            ->and($result->location->isEmpty())->toBeTrue();
    }

    public function test_a_water_leak_is_always_emergency(): void
    {
        $result = AnalysisResult::fromAiResponse([
            'is_maintenance_request' => true,
            'title' => 'Water leaking from ceiling',
            'description' => 'Leak in the pantry.',
            'category' => 'Plumbing',
            'priority' => 'Medium',
            'location' => ['building' => 'Tower A', 'floor' => '5th floor', 'room' => 'pantry'],
            'confidence' => 0.8,
        ], 'Water leaking from ceiling - Tower A, 5th floor pantry');

        expect($result->priority)->toBe(Priority::Emergency);
    }

    public function test_title_is_truncated_to_eighty_characters(): void
    {
        $longTitle = str_repeat('x', 200);

        $result = AnalysisResult::fromAiResponse([
            'is_maintenance_request' => true,
            'title' => $longTitle,
            'description' => 'd',
            'category' => 'Other',
            'priority' => 'Low',
            'location' => null,
            'confidence' => 0.5,
        ], '');

        expect(strlen($result->title))->toBeLessThanOrEqual(80);
    }

    public function test_invalid_category_is_rejected(): void
    {
        $this->expectException(InvalidAiResponseException::class);

        AnalysisResult::fromAiResponse([
            'is_maintenance_request' => true,
            'title' => 'Something broken',
            'description' => 'd',
            'category' => 'NotARealCategory',
            'priority' => 'High',
            'location' => null,
            'confidence' => 0.9,
        ], '');
    }

    public function test_missing_is_maintenance_request_is_rejected(): void
    {
        $this->expectException(InvalidAiResponseException::class);

        AnalysisResult::fromAiResponse([
            'title' => 'Something broken',
        ], '');
    }

    public function test_confidence_is_clamped_to_the_valid_range(): void
    {
        $result = AnalysisResult::fromAiResponse([
            'is_maintenance_request' => true,
            'title' => 'Broken lamp',
            'description' => 'd',
            'category' => 'Electrical',
            'priority' => 'Low',
            'location' => null,
            'confidence' => 2.5,
        ], '');

        expect($result->confidence)->toBe(1.0);
    }

    public function test_category_is_matched_case_insensitively(): void
    {
        $result = AnalysisResult::fromAiResponse([
            'is_maintenance_request' => true,
            'title' => 'Cockroaches',
            'description' => 'd',
            'category' => 'pestcontrol',
            'priority' => 'low',
            'location' => null,
            'confidence' => 0.8,
        ], '');

        expect($result->category)->toBe(Category::PestControl)
            ->and($result->priority)->toBe(Priority::Low);
    }
}