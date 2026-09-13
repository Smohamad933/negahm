<?php

declare(strict_types=1);

namespace App\Core;

/**
 * کپسوله‌سازی درخواست HTTP
 */
final class Request
{
    private static ?Request $instance = null;

    /** @var array<string,mixed>|null */
    private ?array $mergedInput = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public static function setInstance(Request $request): void
    {
        self::$instance = $request;
    }

    public function method(): string
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if ($method === 'POST') {
            $override = strtoupper((string) ($_POST['_method'] ?? ''));
            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                return $override;
            }
        }
        return $method;
    }

    /** مسیر بدون query string و بدون پیشوند نصب */
    public function path(): string
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');

        // حالت سازگار با هاست‌هایی که rewrite ندارند: index.php?r=/brands
        if (isset($_GET['r'])) {
            return '/' . trim((string) $_GET['r'], '/');
        }

        $uri  = explode('?', $uri)[0];
        $base = (string) Config::get('app.base', '');
        $script = (string) ($_SERVER['SCRIPT_NAME'] ?? '');

        if ($base !== '') {
            $pos = strpos($uri, '/' . $base . '/');
            if ($pos !== false) {
                $uri = substr($uri, $pos + strlen($base) + 1);
            }
        } elseif (str_contains($script, '/public/index.php')) {
            $uri = preg_replace('#^' . preg_quote(str_replace('/public/index.php', '', $script), '#') . '/public#', '', $uri) ?? $uri;
        }

        $uri = '/' . trim(rawurldecode($uri), '/');
        return $uri === '/' ? '/' : rtrim($uri, '/');
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        $this->mergedInput ??= array_replace_recursive($_GET, $_POST);
        return $this->mergedInput[$key] ?? $default;
    }

    /** @return array<string,mixed> */
    public function all(): array
    {
        $this->mergedInput ??= array_replace_recursive($_GET, $_POST);
        return $this->mergedInput;
    }

    /** @param array<int,string> $keys */
    public function only(array $keys): array
    {
        $out = [];
        foreach ($keys as $key) {
            $out[$key] = $this->input($key);
        }
        return $out;
    }

    public function boolean(string $key): bool
    {
        $value = $this->input($key);
        return in_array($value, [1, '1', true, 'true', 'on', 'yes'], true);
    }

    public function integer(string $key, int $default = 0): int
    {
        $value = $this->input($key);
        return is_numeric($value) ? (int) $value : $default;
    }

    public function file(string $key): ?array
    {
        return $_FILES[$key] ?? null;
    }

    public function hasFile(string $key): bool
    {
        $file = $_FILES[$key] ?? null;
        return is_array($file)
            && isset($file['error'])
            && (is_array($file['error']) ? count(array_filter((array) $file['error'], static fn ($e) => $e !== UPLOAD_ERR_NO_FILE)) > 0 : $file['error'] !== UPLOAD_ERR_NO_FILE);
    }

    public function header(string $key, ?string $default = null): ?string
    {
        $name = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return $_SERVER[$name] ?? $default;
    }

    public function ip(): string
    {
        foreach (['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = explode(',', (string) $_SERVER[$key])[0];
                return trim($ip);
            }
        }
        return '0.0.0.0';
    }

    public function userAgent(): string
    {
        return (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
    }

    public function isAjax(): bool
    {
        return strtolower((string) ($this->header('X-Requested-With') ?? '')) === 'xmlhttprequest';
    }

    public function wantsJson(): bool
    {
        return $this->isAjax() || str_contains((string) ($this->header('Accept') ?? ''), 'application/json');
    }

    public function fullUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        return $scheme . '://' . $host . (string) ($_SERVER['REQUEST_URI'] ?? '/');
    }

    public function referer(string $default = '/'): string
    {
        $ref = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        return $ref !== '' ? $ref : url($default);
    }

    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }
}
