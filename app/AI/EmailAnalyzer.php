<?php

namespace App\AI;

use App\Models\Email;

interface EmailAnalyzer
{
    /**
     * Analyze an email and return a structured result.
     *
     * @throws Exceptions\AiServiceException        when the provider is unreachable
     * @throws Exceptions\InvalidAiResponseException when the reply is unparseable
     */
    public function analyze(Email $email): AnalysisResult;
}