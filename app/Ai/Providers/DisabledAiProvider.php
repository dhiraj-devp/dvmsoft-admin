<?php

namespace App\Ai\Providers;

use App\Ai\AiResponse;
use App\Ai\Exceptions\AiDisabledException;
use App\Contracts\AiServiceInterface;

class DisabledAiProvider implements AiServiceInterface
{
    public function complete(string $system, string $prompt, array $options = []): AiResponse
    {
        throw new AiDisabledException('AI assistance is disabled.');
    }
}
