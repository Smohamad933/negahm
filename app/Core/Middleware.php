<?php

declare(strict_types=1);

namespace App\Core;

/**
 * میان‌افزارهای مسیر: احراز هویت، نقش کاربر، CSRF و محدودسازی نرخ
 */
final class Middleware
{
    /**
     * @param array<string,mixed> $next
     */
    public static function run(string $name, Request $request, callable $next): mixed
    {
        return match ($name) {
            'auth'   => self::auth($request, $next),
            'guest'  => self::guest($request, $next),
            'admin'  => self::role($request, $next, 'admin'),
            'csrf'   => self::csrf($request, $next),
            'throttle' => self::throttle($request, $next),
            default  => throw new HttpException(500, 'میان‌افزار ناشناخته: ' . $name),
        };
    }

    private static function auth(Request $request, callable $next): mixed
    {
        if (!Auth::check()) {
            if ($request->wantsJson()) {
                throw new HttpException(401, 'احراز هویت لازم است');
            }
            Session::put('intended_url', $request->path());
            Session::flash('warning', 'برای ورود به پنل ابتدا وارد حساب کاربری شوید.');
            throw new RedirectException(url('/admin/login'));
        }
        return $next($request);
    }

    private static function guest(Request $request, callable $next): mixed
    {
        if (Auth::check()) {
            throw new RedirectException(url('/admin'));
        }
        return $next($request);
    }

    private static function role(Request $request, callable $next, string $role): mixed
    {
        $user = Auth::user();
        if ($user === null || ($user['role'] ?? '') !== $role) {
            throw new HttpException(403, 'شما دسترسی لازم برای این بخش را ندارید.');
        }
        return $next($request);
    }

    private static function csrf(Request $request, callable $next): mixed
    {
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $token = (string) ($request->input('_token') ?? $request->header('X-CSRF-Token') ?? '');
            if (!Csrf::verify($token)) {
                throw new HttpException(419, 'توکن امنیتی نامعتبر یا منقضی است. لطفاً صفحه را تازه کنید.');
            }
        }
        return $next($request);
    }

    private static function throttle(Request $request, callable $next): mixed
    {
        $key   = 'throttle_' . sha1($request->ip() . '|' . $request->path());
        $data  = Session::get($key, ['count' => 0, 'reset' => time() + 60]);
        $now   = time();

        if (($data['reset'] ?? 0) < $now) {
            $data = ['count' => 0, 'reset' => $now + 60];
        }
        $data['count']++;
        Session::put($key, $data);

        if ($data['count'] > 10) {
            throw new HttpException(429, 'تعداد درخواست‌ها بیش از حد مجاز است. کمی بعد دوباره تلاش کنید.');
        }

        return $next($request);
    }
}
