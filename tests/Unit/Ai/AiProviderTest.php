<?php

namespace Tests\Unit\Ai;

use App\Ai\Exceptions\AiResponseException;
use App\Ai\Exceptions\AiTimeoutException;
use App\Ai\Providers\FakeAiProvider;
use App\Ai\Providers\OpenAiProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiProviderTest extends TestCase
{
    public function test_fake_provider_returns_structured_payload_for_a_task(): void
    {
        $response = (new FakeAiProvider)->complete('system', 'prompt', ['task' => 'lead']);

        $this->assertSame('fake', $response->provider);
        $this->assertArrayHasKey('summary', $response->data);
        $this->assertArrayHasKey('suggested_status', $response->data);
        $this->assertArrayHasKey('follow_up_message', $response->data);
    }

    public function test_fake_provider_can_simulate_a_timeout(): void
    {
        FakeAiProvider::$failWith = new AiTimeoutException('timed out');

        $this->expectException(AiTimeoutException::class);

        (new FakeAiProvider)->complete('system', 'prompt', ['task' => 'lead']);
    }

    public function test_openai_provider_parses_json_without_a_real_key_roundtrip(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => '{"summary":"ok"}']],
                ],
            ]),
        ]);

        $provider = new OpenAiProvider(
            apiKey: 'test-key',
            model: 'gpt-4o-mini',
            baseUrl: 'https://api.openai.com/v1',
            timeout: 5,
            maxTokens: 200,
        );

        $response = $provider->complete('system', '{"hello":true}', ['task' => 'lead']);

        $this->assertSame('ok', $response->get('summary'));
        $this->assertSame('openai', $response->provider);
    }

    public function test_openai_provider_maps_timeouts_and_invalid_json(): void
    {
        Http::fake(function () {
            throw new ConnectionException('timed out');
        });

        $provider = new OpenAiProvider('test-key', 'gpt-4o-mini', 'https://api.openai.com/v1', 5, 200);

        $this->expectException(AiTimeoutException::class);
        $provider->complete('system', 'prompt');
    }

    public function test_openai_provider_rejects_invalid_json(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'not-json']],
                ],
            ]),
        ]);

        $provider = new OpenAiProvider('test-key', 'gpt-4o-mini', 'https://api.openai.com/v1', 5, 200);

        $this->expectException(AiResponseException::class);
        $provider->complete('system', 'prompt');
    }
}
