<?php

declare(strict_types=1);

namespace App\Core;

/**
 * مدیریت نشست (session) + پیام‌های یک‌بار مصرف (flash)
 */
final class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }
        if (PHP_SAPI === 'cli') {
            $_SESSION ??= [];
            self::$started = true;
            return;
        }

        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/' . ((string) Config::get('app.base', '') ? trim((string) Config::get('app.base', ''), '/') : ''),
            'domain'   => '',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_name('negahm_session');
        session_start();
        self::$started = true;

        // پاک‌سازی پیام‌های فلاش قبلی
        if (isset($_SESSION['_flash_old'])) {
            foreach ((array) $_SESSION['_flash_old'] as $key) {
                unset($_SESSION['_flash'][$key]);
            }
        }
        $_SESSION['_flash_old'] = array_keys($_SESSION['_flash'] ?? []);
    }

    /**
     * بستن نشست جاری و نوشتن آن روی دیسک، در مرز یک درخواست.
     * پس از این فراخوانی، session_start() بعدی با کوکیِ همان درخواست بالا می‌آید.
     */
    public static function close(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        self::$started = false;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    public static function forget(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, mixed $value): void
    {
        self::start();
        $_SESSION['_flash'][$key] = $value;
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION['_flash'][$key] ?? $default;
    }

    public static function hasFlash(string $key): bool
    {
        self::start();
        return isset($_SESSION['_flash'][$key]);
    }

    public static function regenerate(): void
    {
        self::start();
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            session_regenerate_id(true);
        }
    }

    public static function destroy(): void
    {
        self::start();
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            session_destroy();
        }
        $_SESSION = [];
        self::$started = false;
    }
}
