<?php

namespace App\AI;

use App\Enums\Category;
use App\Enums\Priority;
use App\Models\Email;

/**
 * Rule-based EmailAnalyzer used when AI_PROVIDER=fake. Lets the whole
 * pipeline run without any API key, and doubles as a reference for the
 * sample emails in the mailbox seeder.
 */
class FakeAnalyzer implements EmailAnalyzer
{
    public function analyze(Email $email): AnalysisResult
    {
        $subject = mb_strtolower($email->subject);

        $rule = $this->matchRule($subject);

        if ($rule === null) {
            return new AnalysisResult(
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

        [$category, $priority, $building, $floor, $room, $asset, $confidence] = $rule;

        return new AnalysisResult(
            isMaintenanceRequest: true,
            title: mb_strimwidth(trim((string) preg_replace('/^RE:\s*/i', '', $email->subject)), 0, 80),
            description: $email->body_text,
            category: Category::from($category),
            priority: Priority::from($priority),
            location: new Location($building, $floor, $room),
            asset: $asset,
            confidence: $confidence,
        );
    }

    /**
     * @return array{0: string, 1: string, 2: ?string, 3: ?string, 4: ?string, 5: ?string, 6: float}|null
     */
    private function matchRule(string $subject): ?array
    {
        $rules = [
            'water leaking' => ['Plumbing', 'Emergency', 'Tower A', '5th floor', 'pantry', null, 0.95],
            'ceiling leak' => ['Plumbing', 'Emergency', 'Tower A', '5th floor', 'pantry', null, 0.93],
            'ac too warm' => ['HVAC', 'High', null, null, 'meeting room 304', 'FCU-12', 0.85],
            'light flickering' => ['Electrical', 'Medium', 'Parking B2', 'B2', 'near pillar 14', null, 0.8],
            'fire alarm' => ['Fire & Safety', 'Emergency', null, null, null, null, 0.98],
            'power outage' => ['Electrical', 'Emergency', null, null, null, null, 0.98],
            'trapped in the lift' => ['Lifts', 'Emergency', null, null, null, null, 0.95],
            'trapped in lift' => ['Lifts', 'Emergency', null, null, null, null, 0.95],
            'still not fixed' => ['HVAC', 'High', null, null, null, null, 0.7],
        ];

        foreach ($rules as $needle => $rule) {
            if (str_contains($subject, $needle)) {
                return $rule;
            }
        }

        return null;
    }
}