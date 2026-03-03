<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->map('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->map('POST', $path, $handler);
    }

    private function map(string $method, string $path, callable $handler): void
    {
        $paramNames = [];
        $pattern = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', function (array $matches) use (&$paramNames): string {
            $paramNames[] = $matches[1];

            return '([^/]+)';
        }, $path) ?? $path;

        $this->routes[$method][] = [
            'regex' => '#^'.$pattern.'$#',
            'params' => $paramNames,
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $path): void
    {
        header('Content-Type: application/json');

        foreach ($this->routes[$method] ?? [] as $route) {
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }

            array_shift($matches);
            $params = [];
            foreach ($route['params'] as $index => $name) {
                $params[$name] = $matches[$index] ?? null;
            }

            $request = new Request($params);
            $response = new Response();

            ($route['handler'])($request, $response);

            return;
        }

        http_response_code(404);
        echo json_encode(['message' => 'Not found']);
    }
}
