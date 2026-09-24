<?php

namespace App\Contracts;

use App\Ai\AiResponse;

interface AiServiceInterface
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function complete(string $system, string $prompt, array $options = []): AiResponse;
}
