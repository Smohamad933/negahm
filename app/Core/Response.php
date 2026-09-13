<?php

declare(strict_types=1);

namespace App\Core;

/**
 * ساخت و ارسال پاسخ HTTP
 */
final class Response
{
    private string $body = '';
    private int $status = 200;
    /** @var array<string,string> */
    private array $headers = [];

    public function __construct(string $body = '', int $status = 200, array $headers = [])
    {
        $this->body    = $body;
        $this->status  = $status;
        $this->headers = $headers;
    }

    public static function make(string $body, int $status = 200, array $headers = []): self
    {
        return new self($body, $status, $headers);
    }

    public static function json(mixed $data, int $status = 200): self
    {
        return new self(
            (string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
            $status,
            ['Content-Type' => 'application/json; charset=utf-8']
        );
    }

    public static function redirect(string $to, int $status = 302): self
    {
        return new self('', $status, ['Location' => $to]);
    }

    public static function noContent(int $status = 204): self
    {
        return new self('', $status);
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function status(int $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function withCache(int $seconds): self
    {
        return $this
            ->header('Cache-Control', 'public, max-age=' . $seconds)
            ->header('Expires', gmdate('D, d M Y H:i:s', time() + $seconds) . ' GMT');
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers as $name => $value) {
                header($name . ': ' . $value);
            }
            if (!isset($this->headers['Content-Type'])) {
                header('Content-Type: text/html; charset=utf-8');
            }
        }
        echo $this->body;
    }
}
