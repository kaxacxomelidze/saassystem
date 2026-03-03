<?php

declare(strict_types=1);

namespace App\Core;

class Request
{
    public function __construct(private array $routeParams = [])
    {
    }

    public function body(): array
    {
        $input = file_get_contents('php://input');

        return $input ? (json_decode($input, true) ?: []) : [];
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public function header(string $key): ?string
    {
        $normalized = 'HTTP_'.strtoupper(str_replace('-', '_', $key));

        return $_SERVER[$normalized] ?? null;
    }

    public function route(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }
}
