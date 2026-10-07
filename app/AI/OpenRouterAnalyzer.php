<?php

namespace App\AI;

use App\AI\Exceptions\AiServiceException;
use App\AI\Exceptions\InvalidAiResponseException;
use App\Models\Email;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * EmailAnalyzer implementation backed by any OpenRouter-compatible
 * chat-completions endpoint (Llama, Gemma, GPT, Claude, ...).
 */
class OpenRouterAnalyzer implements EmailAnalyzer
{
    public function __construct(
        private readonly PromptProvider $prompts,
        private readonly string $apiKey,
        private readonly string $model,
        private readonly string $baseUrl,
        private readonly int $timeout,
        private readonly bool $verifySsl = true,
    ) {
    }

    public function analyze(Email $email): AnalysisResult
    {
        $messages = $this->prompts->buildMessages($email->subject, $email->body_text);

        try {
            $response = Http::baseUrl($this->baseUrl)
                ->timeout($this->timeout)
                ->withOptions(['verify' => $this->verifySsl])
                ->withToken($this->apiKey)
                ->acceptJson()
                ->post('/chat/completions', [
                    'model' => $this->model,
                    'temperature' => (float) config('ai.openrouter.temperature', 0),
                    'max_tokens' => (int) config('ai.openrouter.max_tokens', 512),
                    'messages' => [
                        ['role' => 'system', 'content' => $messages['system']],
                        ['role' => 'user', 'content' => $messages['user']],
                    ],
                ]);
        } catch (ConnectionException $e) {
            throw new AiServiceException('Could not reach the AI provider: '.$e->getMessage(), 0, $e);
        }

        if ($response->failed()) {
            throw new AiServiceException(
                'AI provider returned HTTP '.$response->status().': '.$response->body()
            );
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new InvalidAiResponseException('AI response was not JSON: '.mb_substr((string) $response->body(), 0, 300));
        }

        if (isset($payload['error'])) {
            $message = is_string($payload['error']['message'] ?? null)
                ? $payload['error']['message']
                : json_encode($payload['error']);

            throw new AiServiceException('AI provider error: '.$message);
        }

        $content = data_get($payload, 'choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            $snippet = mb_substr((string) json_encode($payload), 0, 500);

            throw new InvalidAiResponseException('AI response contained no message content. Raw: '.$snippet);
        }

        $decoded = json_decode($this->stripCodeFences($content), true);

        if (! is_array($decoded)) {
            throw new InvalidAiResponseException('AI response was not valid JSON: '.mb_substr($content, 0, 200));
        }

        return AnalysisResult::fromAiResponse($decoded, $email->subject."\n".$email->body_text);
    }

    /**
     * Free models sometimes wrap JSON in ``` marks.
     */
    private function stripCodeFences(string $content): string
    {
        $content = trim($content);

        if (str_starts_with($content, '```')) {
            $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
            $content = preg_replace('/```\s*$/', '', $content);
        }

        return trim((string) $content);
    }
}