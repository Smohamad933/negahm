<?php

declare(strict_types=1);

namespace App\Core;

/**
 * مسیریاب ساده با پشتیبانی از پارامتر، گروه و میان‌افزار
 */
final class Router
{
    /** @var array<string,array<int,array{pattern:string,regex:string,keys:array<int,string>,handler:mixed,middleware:array<int,string>,name:?string}>> */
    private array $routes = [];

    /** @var array<int,string> */
    private array $groupMiddleware = [];

    /** @var array<int,callable> */
    private array $globalMiddleware = [];

    private string $groupPrefix = '';

    /** @var array<string,string> */
    private array $names = [];

    public function get(string $path, mixed $handler, array $middleware = [], ?string $name = null): void
    {
        $this->add('GET', $path, $handler, $middleware, $name);
    }

    public function post(string $path, mixed $handler, array $middleware = [], ?string $name = null): void
    {
        $this->add('POST', $path, $handler, $middleware, $name);
    }

    public function any(string $path, mixed $handler, array $middleware = [], ?string $name = null): void
    {
        $this->add('GET', $path, $handler, $middleware, $name);
        $this->add('POST', $path, $handler, $middleware, null);
    }

    public function group(string $prefix, array $middleware, callable $callback): void
    {
        $previousPrefix     = $this->groupPrefix;
        $previousMiddleware = $this->groupMiddleware;

        $this->groupPrefix     = $previousPrefix . '/' . trim($prefix, '/');
        $this->groupMiddleware = array_merge($previousMiddleware, $middleware);
        $callback($this);
        $this->groupPrefix     = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;
    }

    public function middleware(callable $middleware): void
    {
        $this->globalMiddleware[] = $middleware;
    }

    private function add(string $method, string $path, mixed $handler, array $middleware, ?string $name): void
    {
        $path = $this->groupPrefix . '/' . trim($path, '/');
        $path = '/' . trim($path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        $keys  = [];
        $regex = (string) preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)(:([^\}]+))?\}/', static function ($m) use (&$keys) {
            $keys[] = $m[1];
            return '(' . ($m[3] ?? '[^/]+') . ')';
        }, $path);

        $this->routes[$method][] = [
            'pattern'    => $path,
            'regex'      => '#^' . $regex . '$#u',
            'keys'       => $keys,
            'handler'    => $handler,
            'middleware' => array_merge($this->groupMiddleware, $middleware),
            'name'       => $name,
        ];

        if ($name !== null) {
            $this->names[$name] = $path;
        }
    }

    /**
     * @return array{0:string,1:array<string,mixed>,2:?array<string,mixed>}
     */
    public function match(string $method, string $path): array
    {
        $path = '/' . trim($path, '/');
        $method = strtoupper($method);

        if (isset($this->routes[$method])) {
            foreach ($this->routes[$method] as $route) {
                if (preg_match($route['regex'], $path, $matches)) {
                    array_shift($matches);
                    $params = [];
                    foreach ($route['keys'] as $i => $key) {
                        $params[$key] = $matches[$i] ?? null;
                    }
                    return ['200', $params, $route];
                }
            }
        }

        // مسیر وجود دارد ولی متد اشتباه است
        $allowed = [];
        foreach ($this->routes as $routeMethod => $routes) {
            foreach ($routes as $route) {
                if (preg_match($route['regex'], $path)) {
                    $allowed[] = $routeMethod;
                }
            }
        }
        if ($allowed !== []) {
            return ['405', [], ['allow' => implode(', ', array_unique($allowed))]];
        }

        return ['404', [], null];
    }

    public function dispatch(string $method, string $path): mixed
    {
        [$status, $params, $route] = $this->match($method, $path);

        if ($status === '404') {
            throw new HttpException(404);
        }
        if ($status === '405') {
            throw new HttpException(405, 'متد درخواست مجاز نیست', ['Allow' => (string) ($route['allow'] ?? 'GET')]);
        }

        $handler    = $route['handler'];
        $middleware = $route['middleware'];

        $pipeline = function (Request $request) use ($handler, $params): mixed {
            return Handler::call($handler, ['request' => $request] + $params);
        };

        foreach (array_reverse($middleware) as $name) {
            $next     = $pipeline;
            $pipeline = static fn (Request $request): mixed => Middleware::run($name, $request, $next);
        }

        foreach (array_reverse($this->globalMiddleware) as $mw) {
            $next     = $pipeline;
            $pipeline = static fn (Request $request): mixed => $mw($request, $next);
        }

        return $pipeline(Request::instance());
    }

    public function url(string $name, array $params = []): string
    {
        $path = $this->names[$name] ?? '/';
        foreach ($params as $key => $value) {
            $path = str_replace('{' . $key . '}', (string) $value, $path);
            $path = (string) preg_replace('#\{[a-zA-Z_][a-zA-Z0-9_]*(:[^\}]+)?\}#', (string) $value, $path, 1);
        }
        return url($path);
    }

    public function names(): array
    {
        return $this->names;
    }
}
