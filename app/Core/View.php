<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * موتور قالب ساده با پشتیبانی از layout و section
 */
final class View
{
    /** @var array<string,mixed> */
    private static array $shared = [];
    /** @var array<string,string> */
    private static array $sections = [];
    /** @var array<int,string> */
    private static array $sectionStack = [];
    private static ?string $layout = null;
    /** @var array<string,int> */
    private static array $renderDepth = [];

    /** @param array<string,mixed> $data */
    public static function share(array $data): void
    {
        self::$shared = array_merge(self::$shared, $data);
    }

    public static function shared(string $key, mixed $default = null): mixed
    {
        return self::$shared[$key] ?? $default;
    }

    /**
     * پاک‌سازی کامل state موتور قالب در مرز یک درخواست
     * (داده‌های اشتراکی، sectionها و layout)
     */
    public static function flush(): void
    {
        self::reset();
        self::$shared      = [];
        self::$renderDepth = [];
    }

    /** @param array<string,mixed> $data */
    public static function render(string $template, array $data = []): string
    {
        $file = self::path($template);
        if ($file === null) {
            throw new RuntimeException('قالب پیدا نشد: ' . $template);
        }

        $previousLayout   = self::$layout;
        $previousSections = self::$sections;

        self::$layout   = null;
        self::$sections = [];

        $content = self::capture($file, array_merge(self::$shared, $data));

        $layout = self::$layout;

        if ($layout !== null) {
            // اگر خودِ ویو بخش «content» را با start/stop ساخته باشد، همان نگه داشته
            // می‌شود؛ در غیر این صورت خروجی مستقیم ویو به‌عنوان content استفاده می‌شود.
            if (!self::hasSection('content')) {
                self::$sections['content'] = $content;
            }

            $layoutFile = self::path($layout);
            if ($layoutFile === null) {
                throw new RuntimeException('لی‌اوت پیدا نشد: ' . $layout);
            }
            $content = self::capture($layoutFile, array_merge(self::$shared, $data));
        }

        self::$layout   = $previousLayout;
        self::$sections = $previousSections;

        return $content;
    }

    /** @param array<string,mixed> $data */
    public static function partial(string $template, array $data = []): string
    {
        $file = self::path($template);
        if ($file === null) {
            return '';
        }
        return self::capture($file, array_merge(self::$shared, $data));
    }

    public static function path(string $template): ?string
    {
        $base     = (string) Config::get('paths.views');
        $template = str_replace('.', '/', ltrim($template, '/'));
        $file     = $base . '/' . $template . '.php';
        return is_file($file) ? $file : null;
    }

    /** @param array<string,mixed> $data */
    private static function capture(string $__file, array $data): string
    {
        $depth = self::$renderDepth[$__file] ?? 0;
        self::$renderDepth[$__file] = $depth + 1;
        try {
            extract($data, EXTR_SKIP);
            ob_start();
            include $__file;
            return (string) ob_get_clean();
        } catch (\Throwable $e) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            throw $e;
        } finally {
            self::$renderDepth[$__file] = $depth;
        }
    }

    public static function start(string $section): void
    {
        self::$sectionStack[] = $section;
        ob_start();
    }

    public static function stop(): void
    {
        $section = array_pop(self::$sectionStack);
        $content = (string) ob_get_clean();
        if ($section !== null) {
            self::$sections[$section] = $content;
        }
    }

    public static function append(string $section): void
    {
        $sectionName = array_pop(self::$sectionStack);
        $content     = (string) ob_get_clean();
        $key         = $sectionName ?? $section;
        self::$sections[$key] = (self::$sections[$key] ?? '') . $content;
    }

    public static function section(string $name, string $default = ''): string
    {
        return self::$sections[$name] ?? $default;
    }

    public static function hasSection(string $name): bool
    {
        return isset(self::$sections[$name]) && trim(self::$sections[$name]) !== '';
    }

    public static function extend(string $layout): void
    {
        self::$layout = $layout;
    }

    public static function reset(): void
    {
        self::$sections     = [];
        self::$sectionStack = [];
        self::$layout       = null;
    }
}
