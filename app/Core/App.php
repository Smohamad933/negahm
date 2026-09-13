<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

/**
 * راه‌انداز برنامه: autoload، پیکربندی، نشست، مسیریابی و مدیریت خطا
 */
final class App
{
    private static ?Router $router = null;
    /** @var array<string,mixed> */
    private static array $config = [];

    public static function boot(string $root): void
    {
        self::registerAutoloader($root);

        Env::load($root . '/.env');
        self::$config = require $root . '/config/config.php';
        Config::load(self::$config);

        date_default_timezone_set((string) Config::get('app.timezone', 'Asia/Tehran'));
        mb_internal_encoding('UTF-8');

        if (!Config::get('app.debug')) {
            ini_set('display_errors', '0');
        }
        error_reporting(E_ALL);

        require_once $root . '/app/Core/helpers.php';

        Session::start();
    }

    private static function registerAutoloader(string $root): void
    {
        spl_autoload_register(static function (string $class) use ($root): void {
            if (!str_starts_with($class, 'App\\')) {
                return;
            }
            $relative = substr($class, strlen('App\\'));
            $file     = $root . '/app/' . str_replace('\\', '/', $relative) . '.php';
            if (is_file($file)) {
                require_once $file;
            }
        });
    }

    public static function router(): ?Router
    {
        return self::$router;
    }

    public static function run(?Router $router = null): void
    {
        $router ??= require Config::get('paths.root') . '/routes/web.php';
        self::$router = $router;

        $request = Request::instance();
        Request::setInstance($request);

        try {
            $result = $router->dispatch($request->method(), $request->path());
            self::trackVisit($request, $result);
            self::send($result);
        } catch (RedirectException $e) {
            self::send(Response::redirect($e->to(), $e->status()));
        } catch (HttpException $e) {
            self::send(self::errorResponse($e->status(), $e->getMessage(), $e));
        } catch (Throwable $e) {
            self::send(self::errorResponse(500, $e->getMessage(), $e));
        }
    }

    private static function send(mixed $result): void
    {
        if ($result instanceof Response) {
            $result->send();
            return;
        }
        if (is_string($result)) {
            (new Response($result))->send();
            return;
        }
        if (is_array($result)) {
            Response::json($result)->send();
            return;
        }
        (new Response(''))->send();
    }

    /**
     * ثبت بازدید صفحه در آمار سایت.
     *
     * فقط برای درخواست‌های GET سمت سایت و فقط وقتی صفحه با موفقیت رندر شده
     * باشد. هر خطایی اینجا نادیده گرفته می‌شود تا آمار، سایت را از کار نیندازد.
     */
    private static function trackVisit(Request $request, mixed $result): void
    {
        if ($request->method() !== 'GET') {
            return;
        }

        $path = $request->path();
        if (str_starts_with($path, '/admin') || $path === '/sitemap.xml' || $path === '/robots.txt') {
            return;
        }

        $status = $result instanceof Response ? $result->getStatus() : 200;
        if ($status >= 400) {
            return;
        }

        try {
            \App\Models\Visit::track($path);
        } catch (Throwable) {
            // آمار حیاتی نیست
        }
    }

    private static function errorResponse(int $status, string $message, Throwable $e): Response
    {
        self::logException($e);

        $request = Request::instance();
        $isJson  = $request->wantsJson() || str_starts_with($request->path(), '/api/');

        if ($isJson) {
            return Response::json([
                'ok'      => false,
                'status'  => $status,
                'message' => Config::get('app.debug') ? $message : self::publicMessage($status),
            ], $status);
        }

        $template = match ($status) {
            404 => 'errors.404',
            403 => 'errors.403',
            500 => 'errors.500',
            default => 'errors.generic',
        };

        try {
            View::reset();
            $body = View::render($template, [
                'status'  => $status,
                'message' => Config::get('app.debug') && $message !== '' ? $message : self::publicMessage($status),
                'trace'   => Config::get('app.debug') ? $e->getTraceAsString() : '',
                'file'    => Config::get('app.debug') ? $e->getFile() . ':' . $e->getLine() : '',
            ]);
        } catch (Throwable) {
            $body = '<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><title>'
                . e(self::publicMessage($status)) . '</title></head><body style="font-family:sans-serif;text-align:center;padding:4rem">'
                . '<h1>' . $status . '</h1><p>' . e(self::publicMessage($status)) . '</p></body></html>';
        }

        $response = new Response($body, $status);
        foreach ($e instanceof HttpException ? $e->headers() : [] as $name => $value) {
            $response->header($name, $value);
        }
        return $response;
    }

    private static function publicMessage(int $status): string
    {
        return match ($status) {
            403     => 'دسترسی به این صفحه مجاز نیست.',
            404     => 'صفحه‌ای که دنبالش بودید پیدا نشد.',
            405     => 'این درخواست پشتیبانی نمی‌شود.',
            419     => 'نشست شما منقضی شده است؛ لطفاً صفحه را تازه کنید.',
            429     => 'تعداد درخواست‌ها بیش از حد مجاز است.',
            default => 'خطای غیرمنتظره‌ای رخ داد. لطفاً دوباره تلاش کنید.',
        };
    }

    private static function logException(Throwable $e): void
    {
        if ($e instanceof HttpException && $e->status() < 500) {
            return;
        }
        $dir = (string) Config::get('paths.storage') . '/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $line = sprintf(
            "[%s] %s: %s in %s:%d\n%s\n\n",
            date('Y-m-d H:i:s'),
            $e::class,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
        @file_put_contents($dir . '/app-' . date('Y-m-d') . '.log', $line, FILE_APPEND | LOCK_EX);
    }
}
