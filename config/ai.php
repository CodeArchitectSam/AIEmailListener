<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI provider
    |--------------------------------------------------------------------------
    |
    | The EmailAnalyzer implementation is resolved from this value in
    | AppServiceProvider. Swap the provider without touching business code.
    |
    */

    'provider' => env('AI_PROVIDER', 'openrouter'),

    'openrouter' => [
        'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
        'api_key' => env('OPENROUTER_API_KEY'),
        'model' => env('AI_MODEL', 'google/gemma-4-31b-it:free'),
        'temperature' => (float) env('AI_TEMPERATURE', 0),
        'max_tokens' => (int) env('AI_MAX_TOKENS', 512),
        'timeout' => (int) env('AI_TIMEOUT', 90),
        'verify_ssl' => (bool) env('AI_VERIFY_SSL', true),
    ],

    /*
    | Below this confidence value the created task goes to "Needs Review".
    */
    'confidence_threshold' => (float) env('AI_CONFIDENCE_THRESHOLD', 0.7),

    /*
    | The prompt lives in exactly one place: this file.
    */
    'prompt_path' => resource_path('prompts/maintenance-analysis.txt'),

];