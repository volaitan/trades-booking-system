<?php
declare(strict_types=1);

namespace App;

/** Minimal router: '/admin/bookings/{id}' style patterns, numeric ids only. */
final class Router
{
    /** @var list<array{string, string, callable}> */
    private array $routes = [];

    public function get(string $pattern, callable $handler): void
    {
        $this->routes[] = ['GET', $pattern, $handler];
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->routes[] = ['POST', $pattern, $handler];
    }

    /** @return array{0: callable, 1: array<string, string>}|null  handler and route params; null if not found */
    public function match(string $method, string $path): ?array
    {
        $path = '/' . trim(parse_url($path, PHP_URL_PATH) ?: '/', '/');
        foreach ($this->routes as [$m, $pattern, $handler]) {
            $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>\d+)', rtrim($pattern, '/') ?: '/') . '$#';
            if ($m === $method && preg_match($regex, $path, $matches)) {
                return [$handler, array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY)];
            }
        }
        return null;
    }

    public function allowsOtherMethod(string $method, string $path): bool
    {
        $other = $method === 'GET' ? 'POST' : 'GET';
        return $this->match($other, $path) !== null;
    }
}
