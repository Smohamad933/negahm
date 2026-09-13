<?php

declare(strict_types=1);

namespace App\Core;

/**
 * خطای HTTP — کد وضعیت + هدرها
 */
class HttpException extends \RuntimeException
{
    /** @var array<string,string> */
    private array $headers;

    /** @param array<string,string> $headers */
    public function __construct(private int $status = 500, string $message = '', array $headers = [])
    {
        parent::__construct($message !== '' ? $message : 'HTTP ' . $status);
        $this->headers = $headers;
    }

    public function status(): int
    {
        return $this->status;
    }

    /** @return array<string,string> */
    public function headers(): array
    {
        return $this->headers;
    }
}
