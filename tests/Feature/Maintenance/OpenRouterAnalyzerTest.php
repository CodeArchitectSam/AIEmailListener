<?php

namespace Tests\Feature\Maintenance;

use App\AI\EmailAnalyzer;
use App\AI\Exceptions\AiServiceException;
use App\AI\OpenRouterAnalyzer;
use App\AI\PromptProvider;
use App\Models\Email;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenRouterAnalyzerTest extends TestCase
{
    use RefreshDatabase;

    private function analyzer(): OpenRouterAnalyzer
    {
        return new OpenRouterAnalyzer(
            prompts: new PromptProvider,
            apiKey: 'test-key',
            model: 'test-model',
            baseUrl: 'https://openrouter.test/api/v1',
            timeout: 10,
        );
    }

    public function test_it_sends_the_email_and_builds_a_result_from_the_reply(): void
    {
        Http::fake([
            'https://openrouter.test/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode([
                        'is_maintenance_request' => true,
                        'title' => 'AC not cooling in Tower B, Room 304',
                        'description' => 'Room stays warm.',
                        'category' => 'HVAC',
                        'priority' => 'High',
                        'location' => ['building' => 'Tower B', 'floor' => null, 'room' => 'Room 304'],
                        'asset' => 'FCU-12',
                        'confidence' => 0.9,
                    ])]],
                ],
            ]),
        ]);

        $email = Email::factory()->create([
            'subject' => 'AC not cooling in Tower B, Room 304',
            'body_text' => 'The AC is not cooling.',
        ]);

        $result = $this->analyzer()->analyze($email);

        expect($result->isMaintenanceRequest)->toBeTrue()
            ->and($result->category->value)->toBe('HVAC')
            ->and($result->location->building)->toBe('Tower B');

        Http::assertSent(fn ($request) => str_contains($request->url(), '/chat/completions')
            && $request['model'] === 'test-model'
            && $request['messages'][0]['role'] === 'system');
    }

    public function test_it_strips_markdown_code_fences_from_the_reply(): void
    {
        Http::fake([
            'https://openrouter.test/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => "```json\n".json_encode([
                        'is_maintenance_request' => true,
                        'title' => 'Broken lamp',
                        'description' => 'Lamp is out.',
                        'category' => 'Electrical',
                        'priority' => 'Low',
                        'location' => null,
                        'asset' => null,
                        'confidence' => 0.8,
                    ])."\n```"]],
                ],
            ]),
        ]);

        $email = Email::factory()->create(['subject' => 'Broken lamp']);

        $result = $this->analyzer()->analyze($email);

        expect($result->title)->toBe('Broken lamp');
    }

    public function test_an_http_error_raises_an_ai_service_exception(): void
    {
        Http::fake(['https://openrouter.test/*' => Http::response('boom', 500)]);

        $email = Email::factory()->create();

        $this->expectException(AiServiceException::class);

        $this->analyzer()->analyze($email);
    }

    public function test_invalid_json_raises_an_invalid_response_exception(): void
    {
        Http::fake([
            'https://openrouter.test/*' => Http::response([
                'choices' => [['message' => ['content' => 'definitely not json']]],
            ]),
        ]);

        $email = Email::factory()->create();

        $this->expectException(\App\AI\Exceptions\InvalidAiResponseException::class);

        $this->analyzer()->analyze($email);
    }

    public function test_a_connection_failure_raises_an_ai_service_exception(): void
    {
        Http::fake(['https://openrouter.test/*' => fn () => throw new ConnectionException('timeout')]);

        $email = Email::factory()->create();

        $this->expectException(AiServiceException::class);

        $this->analyzer()->analyze($email);
    }
}