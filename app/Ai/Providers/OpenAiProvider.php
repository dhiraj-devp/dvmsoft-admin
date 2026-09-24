<?php

namespace App\Ai\Providers;

use App\Ai\AiResponse;
use App\Ai\Exceptions\AiConfigurationException;
use App\Ai\Exceptions\AiException;
use App\Ai\Exceptions\AiResponseException;
use App\Ai\Exceptions\AiTimeoutException;
use App\Contracts\AiServiceInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

class OpenAiProvider implements AiServiceInterface
{
    public function __construct(
        protected string $apiKey,
        protected string $model,
        protected string $baseUrl,
        protected int $timeout,
        protected int $maxTokens,
    ) {}

    public function complete(string $system, string $prompt, array $options = []): AiResponse
    {
        $model = (string) ($options['model'] ?? $this->model);
        $maxTokens = (int) ($options['max_tokens'] ?? $this->maxTokens);

        try {
            $response = Http::baseUrl(rtrim($this->baseUrl, '/'))
                ->withToken($this->apiKey)
                ->acceptJson()
                ->timeout($this->timeout)
                ->connectTimeout(min(5, $this->timeout))
                ->post('/chat/completions', [
                    'model' => $model,
                    'max_tokens' => $maxTokens,
                    'temperature' => 0.2,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);
        } catch (ConnectionException $exception) {
            throw new AiTimeoutException('The AI provider timed out.', previous: $exception);
        } catch (Throwable $exception) {
            throw new AiException('The AI provider could not be reached.', previous: $exception);
        }

        if ($response->status() === 401 || $response->status() === 403) {
            throw new AiConfigurationException('The AI provider rejected the configured credentials.');
        }

        if ($response->failed()) {
            throw new AiException('The AI provider returned an error (HTTP '.$response->status().').');
        }

        $content = (string) data_get($response->json(), 'choices.0.message.content', '');
        $data = $this->decode($content);

        return new AiResponse($data, 'openai', $model);
    }

    /**
     * @return array<string, mixed>
     */
    protected function decode(string $content): array
    {
        $content = trim($content);
        $content = preg_replace('/^```json\s*|\s*```$/', '', $content) ?? $content;

        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            throw new AiResponseException('The AI provider did not return valid JSON.');
        }

        return $decoded;
    }
}
