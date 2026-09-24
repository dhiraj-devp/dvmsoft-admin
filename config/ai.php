<?php

return [
    'enabled' => filter_var(env('AI_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    'provider' => env('AI_PROVIDER', 'openai'),
    'model' => env('AI_MODEL', 'gpt-4o-mini'),
    'api_key' => env('AI_API_KEY'),
    'base_url' => env('AI_BASE_URL', 'https://api.openai.com/v1'),
    'timeout' => (int) env('AI_TIMEOUT', 20),
    'max_tokens' => (int) env('AI_MAX_TOKENS', 1200),
    'rate_limit_per_minute' => (int) env('AI_RATE_LIMIT', 20),
];
