<?php

namespace App\Ai;

class PromptSanitizer
{
    /**
     * @var list<string>
     */
    protected array $sensitiveKeys = [
        'password',
        'password_confirmation',
        'current_password',
        'remember_token',
        'api_key',
        'api_token',
        'access_token',
        'refresh_token',
        'token',
        'secret',
        'authorization',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'bank_account_number',
        'bank_ifsc',
        'bank_name',
        'upi_id',
        'pan_number',
        'ai_api_key',
        'openai_key',
    ];

    public function scrub(mixed $value): mixed
    {
        if (is_array($value)) {
            $clean = [];

            foreach ($value as $key => $item) {
                if ($this->isSensitiveKey((string) $key)) {
                    $clean[$key] = '[redacted]';

                    continue;
                }

                $clean[$key] = $this->scrub($item);
            }

            return $clean;
        }

        if (! is_string($value)) {
            return $value;
        }

        $value = preg_replace('/sk-[A-Za-z0-9_\-]{10,}/', '[redacted-key]', $value) ?? $value;
        $value = preg_replace('/Bearer\s+[A-Za-z0-9._\-]+/i', 'Bearer [redacted]', $value) ?? $value;
        $value = preg_replace('/\b(?:AKIA)[A-Z0-9]{16}\b/', '[redacted-aws]', $value) ?? $value;
        $value = preg_replace(
            '/(password|passwd|api[_-]?key|secret|token|authorization)\s*[:=]\s*\S+/i',
            '$1=[redacted]',
            $value
        ) ?? $value;

        return $value;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function encode(array $context): string
    {
        return json_encode($this->scrub($context), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
    }

    protected function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower($key);

        if (in_array($normalized, $this->sensitiveKeys, true)) {
            return true;
        }

        return (bool) preg_match('/(password|secret|token|api[_-]?key)$/i', $normalized);
    }
}
