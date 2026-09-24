<?php

namespace App\Ai;

use App\Ai\Exceptions\AiConfigurationException;
use App\Ai\Exceptions\AiDisabledException;
use App\Ai\Providers\DisabledAiProvider;
use App\Ai\Providers\FakeAiProvider;
use App\Ai\Providers\OpenAiProvider;
use App\Contracts\AiServiceInterface;

class AiManager
{
    public function enabled(): bool
    {
        if (! (bool) config('ai.enabled')) {
            return false;
        }

        return (bool) settings('ai.enabled', false);
    }

    public function providerName(): string
    {
        $stored = settings('ai.provider', '');

        if (is_string($stored) && $stored !== '') {
            return $stored;
        }

        return (string) config('ai.provider', 'openai');
    }

    public function model(): string
    {
        $stored = settings('ai.model', '');

        if (is_string($stored) && $stored !== '') {
            return $stored;
        }

        return (string) config('ai.model', 'gpt-4o-mini');
    }

    public function timeout(): int
    {
        $stored = (int) settings('ai.timeout', 0);

        return $stored > 0 ? $stored : (int) config('ai.timeout', 20);
    }

    public function maxTokens(): int
    {
        $stored = (int) settings('ai.max_tokens', 0);

        return $stored > 0 ? $stored : (int) config('ai.max_tokens', 1200);
    }

    public function apiKeyConfigured(): bool
    {
        return filled(config('ai.api_key'));
    }

    public function driver(?string $name = null): AiServiceInterface
    {
        if (! $this->enabled()) {
            return new DisabledAiProvider;
        }

        $name ??= $this->providerName();

        return match ($name) {
            'fake' => new FakeAiProvider,
            'openai' => $this->openAi(),
            default => throw new AiConfigurationException('Unknown AI provider.'),
        };
    }

    protected function openAi(): OpenAiProvider
    {
        $key = config('ai.api_key');

        if (! is_string($key) || $key === '') {
            throw new AiConfigurationException('AI_API_KEY is not configured in the environment.');
        }

        return new OpenAiProvider(
            apiKey: $key,
            model: $this->model(),
            baseUrl: (string) config('ai.base_url'),
            timeout: $this->timeout(),
            maxTokens: $this->maxTokens(),
        );
    }

    public function ensureEnabled(): void
    {
        if (! $this->enabled()) {
            throw new AiDisabledException('AI assistance is disabled.');
        }
    }
}
