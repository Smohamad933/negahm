<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;

/**
 * احراز هویت کاربر پنل
 */
final class Auth
{
    /** @var array<string,mixed>|null */
    private static ?array $user = null;
    private static bool $resolved = false;

    /**
     * پاک‌سازی کاربر کش‌شده در مرز یک درخواست؛
     * تا کاربرِ درخواست قبلی به درخواست بعدی نشت نکند.
     */
    public static function reset(): void
    {
        self::$user     = null;
        self::$resolved = false;
    }

    public static function attempt(string $identifier, string $password, bool $remember = false): bool
    {
        $user = User::findByIdentifier($identifier);

        if ($user === null || !password_verify($password, (string) $user['password'])) {
            self::registerFailedAttempt($identifier);
            return false;
        }

        if ((int) ($user['is_active'] ?? 0) !== 1) {
            Session::flash('danger', 'حساب کاربری شما غیرفعال است.');
            return false;
        }

        self::login($user, $remember);
        return true;
    }

    /** @param array<string,mixed> $user */
    public static function login(array $user, bool $remember = false): void
    {
        Session::regenerate();
        Session::put('user_id', (int) $user['id']);
        Session::put('user_role', (string) $user['role']);
        self::$user     = $user;
        self::$resolved = true;

        User::touchLogin((int) $user['id']);

        if ($remember) {
            self::issueRememberToken((int) $user['id']);
        }
        Csrf::rotate();
    }

    public static function logout(): void
    {
        $id = (int) Session::get('user_id', 0);
        if ($id > 0) {
            User::clearRememberToken($id);
        }
        if (isset($_COOKIE['negahm_remember'])) {
            setcookie('negahm_remember', '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
        }
        Session::destroy();
        self::$user     = null;
        self::$resolved = false;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    /** @return array<string,mixed>|null */
    public static function user(): ?array
    {
        if (self::$resolved) {
            return self::$user;
        }
        self::$resolved = true;

        $id = (int) Session::get('user_id', 0);
        if ($id > 0) {
            $user = User::find($id);
            if ($user !== null && (int) ($user['is_active'] ?? 0) === 1) {
                self::$user = $user;
                return $user;
            }
        }

        // ورود خودکار با کوکی «مرا به خاطر بسپار»
        $user = self::loginFromRememberCookie();
        if ($user !== null) {
            self::$user = $user;
            return $user;
        }

        self::$user = null;
        return null;
    }

    public static function id(): int
    {
        return (int) (self::user()['id'] ?? 0);
    }

    public static function role(): string
    {
        return (string) (self::user()['role'] ?? '');
    }

    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    private static function issueRememberToken(int $userId): void
    {
        $selector = bin2hex(random_bytes(9));
        $verifier = bin2hex(random_bytes(32));
        User::storeRememberToken($userId, $selector, hash('sha256', $verifier));

        $value = $selector . ':' . $verifier;
        setcookie('negahm_remember', $value, [
            'expires'  => time() + 60 * 60 * 24 * 30,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]);
    }

    /** @return array<string,mixed>|null */
    private static function loginFromRememberCookie(): ?array
    {
        $cookie = (string) ($_COOKIE['negahm_remember'] ?? '');
        if (!str_contains($cookie, ':')) {
            return null;
        }
        [$selector, $verifier] = explode(':', $cookie, 2);

        $user = User::findByRememberSelector($selector);
        if ($user === null) {
            return null;
        }
        if (!hash_equals((string) $user['remember_verifier'], hash('sha256', $verifier))) {
            User::clearRememberToken((int) $user['id']);
            return null;
        }
        if (strtotime((string) ($user['remember_expires'] ?? 'now')) < time()) {
            User::clearRememberToken((int) $user['id']);
            return null;
        }

        Session::put('user_id', (int) $user['id']);
        Session::put('user_role', (string) $user['role']);
        self::issueRememberToken((int) $user['id']);

        return User::find((int) $user['id']);
    }

    /** ثبت تلاش‌های ناموفق ورود (محدودسازی نرخ) */
    private static function registerFailedAttempt(string $identifier): void
    {
        $key  = 'login_attempts_' . sha1($identifier . '|' . Request::instance()->ip());
        $data = Session::get($key, ['count' => 0, 'locked_until' => 0]);
        $data['count']++;
        if ($data['count'] >= 5) {
            $data['locked_until'] = time() + 300;
            $data['count']        = 0;
        }
        Session::put($key, $data);
    }

    public static function isLocked(string $identifier): int
    {
        $key  = 'login_attempts_' . sha1($identifier . '|' . Request::instance()->ip());
        $data = Session::get($key, ['count' => 0, 'locked_until' => 0]);
        $left = (int) $data['locked_until'] - time();
        return $left > 0 ? $left : 0;
    }
}
