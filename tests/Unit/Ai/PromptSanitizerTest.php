<?php

namespace Tests\Unit\Ai;

use App\Ai\PromptSanitizer;
use Tests\TestCase;

class PromptSanitizerTest extends TestCase
{
    public function test_it_redacts_sensitive_keys_and_secret_patterns(): void
    {
        $sanitizer = new PromptSanitizer;

        $clean = $sanitizer->scrub([
            'password' => 'hunter2',
            'api_key' => 'sk-live-should-go',
            'notes' => 'password=supersecret token=abc123 Bearer abc.def',
            'requirement' => 'Need a portal. sk-abcdefghijklmnopqrstuvwxyz',
            'pan_number' => 'ABCDE1234F',
            'bank_account_number' => '1234567890',
            'safe' => 'Website redesign',
        ]);

        $this->assertSame('[redacted]', $clean['password']);
        $this->assertSame('[redacted]', $clean['api_key']);
        $this->assertSame('[redacted]', $clean['pan_number']);
        $this->assertSame('[redacted]', $clean['bank_account_number']);
        $this->assertSame('Website redesign', $clean['safe']);
        $this->assertStringNotContainsString('supersecret', $clean['notes']);
        $this->assertStringNotContainsString('hunter2', json_encode($clean));
        $this->assertStringNotContainsString('sk-abcdefghijklmnopqrstuvwxyz', $clean['requirement']);
    }
}
