<?php

namespace App\Ai;

class AiResponse
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly array $data,
        public readonly string $provider,
        public readonly string $model,
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->data, $key, $default);
    }
}
