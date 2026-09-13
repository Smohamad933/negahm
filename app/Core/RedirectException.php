<?php

declare(strict_types=1);

namespace App\Core;

/**
 * استثنا برای تغییر مسیر (redirect) از داخل کنترلر یا میان‌افزار
 */
class RedirectException extends \RuntimeException
{
    public function __construct(private string $to, private int $status = 302)
    {
        parent::__construct('Redirect to ' . $to);
    }

    public function to(): string
    {
        return $this->to;
    }

    public function status(): int
    {
        return $this->status;
    }
}
