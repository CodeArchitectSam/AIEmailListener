<?php

namespace App\AI;

use Illuminate\Support\Facades\File;

/**
 * Single source of truth for the AI prompt. The prompt text lives in a
 * plain file (resources/prompts) so it can be edited without touching code.
 */
class PromptProvider
{
    private ?string $cached = null;

    public function getMaintenancePrompt(): string
    {
        return $this->cached ??= File::get(config('ai.prompt_path'));
    }

    /**
     * @return array{system: string, user: string}
     */
    public function buildMessages(string $subject, string $body): array
    {
        return [
            'system' => $this->getMaintenancePrompt(),
            'user' => "SUBJECT:\n{$subject}\n\nBODY:\n{$body}",
        ];
    }
}