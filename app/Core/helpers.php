<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Jalali;
use App\Core\Request;
use App\Core\Router;
use App\Core\Session;
use App\Core\Str;
use App\Core\View;

if (!function_exists('e')) {
    /** خروجی امن HTML */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('url')) {
    /** ساخت آدرس مطلق با احتساب مسیر پایه نصب */
    function url(string $path = '/'): string
    {
        if (preg_match('#^(https?:)?//#', $path) || str_starts_with($path, 'mailto:') || str_starts_with($path, 'tel:')) {
            return $path;
        }
        $base = (string) Config::get('app.base', '');
        $prefix = $base !== '' ? '/' . trim($base, '/') : '';
        $path = '/' . ltrim($path, '/');
        return ($prefix . $path) === '/' ? '/' : rtrim($prefix . $path, '/');
    }
}

if (!function_exists('asset')) {
    /** آدرس فایل‌های استاتیک با version برای کش‌شکنی */
    function asset(string $path): string
    {
        $path = ltrim($path, '/');
        $file = Config::get('paths.public') . '/' . $path;
        $version = is_file((string) $file) ? (string) filemtime((string) $file) : '1';
        return url('/assets/' . $path) . '?v=' . $version;
    }
}

if (!function_exists('upload_url')) {
    /** آدرس فایل آپلودشده */
    function upload_url(?string $path, string $fallback = ''): string
    {
        if ($path === null || $path === '') {
            return $fallback !== '' ? asset($fallback) : '';
        }
        if (preg_match('#^(https?:)?//#', $path)) {
            return $path;
        }
        return url('/' . ltrim($path, '/'));
    }
}

if (!function_exists('setting')) {
    /** خواندن یک تنظیم از جدول settings (با کش در حافظه) */
    function setting(string $key, mixed $default = ''): mixed
    {
        $cache = &setting_cache();
        if ($cache === null) {
            $cache = [];
            try {
                $sql = sprintf(
                    'SELECT %s, %s, %s FROM %s',
                    App\Core\DB::quoteIdent('key'),
                    App\Core\DB::quoteIdent('value'),
                    App\Core\DB::quoteIdent('type'),
                    App\Core\DB::quoteIdent('settings')
                );
                foreach (App\Core\DB::select($sql) as $row) {
                    $cache[$row['key']] = match ($row['type']) {
                        'int'    => (int) $row['value'],
                        'bool'   => in_array((string) $row['value'], ['1', 'true', 'on', 'yes'], true),
                        'json'   => json_decode((string) $row['value'], true),
                        default  => (string) $row['value'],
                    };
                }
            } catch (Throwable) {
                $cache = [];
            }
        }
        $value = $cache[$key] ?? null;
        return ($value === null || $value === '') ? $default : $value;
    }
}

if (!function_exists('setting_cache')) {
    /**
     * اشاره‌گر به کش تنظیمات.
     * @return array<string,mixed>|null
     */
    function &setting_cache(): ?array
    {
        static $cache = null;
        return $cache;
    }
}

if (!function_exists('setting_flush')) {
    /**
     * خالی کردن کش تنظیمات.
     * بعد از هر تغییر در جدول settings صدا زده می‌شود تا مقدار تازه خوانده شود.
     */
    function setting_flush(): void
    {
        $cache = &setting_cache();
        $cache = null;
    }
}

if (!function_exists('settings_all')) {
    /** @return array<string,mixed> */
    function settings_all(): array
    {
        $out = [];
        try {
            foreach (App\Core\DB::select('SELECT * FROM ' . App\Core\DB::quoteIdent('settings')) as $row) {
                $out[$row['key']] = $row;
            }
        } catch (Throwable) {
            //
        }
        return $out;
    }
}

if (!function_exists('old')) {
    /** مقدار قبلی فرم (بعد از خطای اعتبارسنجی) */
    function old(string $key, mixed $default = ''): mixed
    {
        $old = Session::getFlash('_old', []);
        return is_array($old) && array_key_exists($key, $old) ? $old[$key] : $default;
    }
}

if (!function_exists('errors')) {
    /** @return array<string,string> */
    function errors(): array
    {
        $errors = Session::getFlash('_errors', []);
        return is_array($errors) ? $errors : [];
    }
}

if (!function_exists('error_for')) {
    function error_for(string $field): string
    {
        return errors()[$field] ?? '';
    }
}

if (!function_exists('has_error')) {
    function has_error(string $field): bool
    {
        return error_for($field) !== '';
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return Csrf::field();
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Csrf::token();
    }
}

if (!function_exists('route')) {
    function route(string $name, array $params = []): string
    {
        /** @var Router|null $router */
        $router = App\Core\App::router();
        return $router !== null ? $router->url($name, $params) : url('/');
    }
}

if (!function_exists('request')) {
    function request(): Request
    {
        return Request::instance();
    }
}

if (!function_exists('auth')) {
    /** @return array<string,mixed>|null */
    function auth(): ?array
    {
        return Auth::user();
    }
}

if (!function_exists('is_admin')) {
    function is_admin(): bool
    {
        return Auth::isAdmin();
    }
}

if (!function_exists('view')) {
    /** @param array<string,mixed> $data */
    function view(string $template, array $data = []): string
    {
        return View::render($template, $data);
    }
}

if (!function_exists('partial')) {
    /** @param array<string,mixed> $data */
    function partial(string $template, array $data = []): string
    {
        return View::partial($template, $data);
    }
}

if (!function_exists('jdate')) {
    function jdate(?string $datetime, string $pattern = 'j F Y', bool $withTime = false): string
    {
        return Jalali::format($datetime, $pattern, $withTime);
    }
}

if (!function_exists('jdigits')) {
    function jdigits(mixed $value): string
    {
        return Str::toPersianDigits($value);
    }
}

if (!function_exists('excerpt')) {
    function excerpt(?string $text, int $limit = 160): string
    {
        return Str::excerpt($text, $limit);
    }
}

if (!function_exists('is_current')) {
    /** آیا مسیر فعلی با الگوی داده‌شده شروع می‌شود؟ */
    function is_current(string $prefix, bool $exact = false): bool
    {
        $path = Request::instance()->path();
        if ($prefix === '/') {
            return $path === '/';
        }
        $prefix = '/' . trim($prefix, '/');
        return $exact ? $path === $prefix : str_starts_with($path, $prefix);
    }
}

if (!function_exists('active_class')) {
    function active_class(string $prefix, string $class = 'is-active', bool $exact = false): string
    {
        return is_current($prefix, $exact) ? $class : '';
    }
}

if (!function_exists('flash_render')) {
    /** نمایش پیام‌های فلاش */
    function flash_render(): string
    {
        $html = '';
        foreach (['success' => 'موفق', 'danger' => 'خطا', 'warning' => 'توجه', 'info' => 'اعلان'] as $type => $label) {
            if (Session::hasFlash($type)) {
                $html .= '<div class="alert alert-' . $type . '" role="alert"><span class="alert-icon">✦</span><div>'
                    . e((string) Session::getFlash($type)) . '</div><button type="button" class="alert-close" aria-label="بستن">×</button></div>';
            }
        }
        return $html;
    }
}

if (!function_exists('pagination_links')) {
    /** @param array{page:int,lastPage:int} $pager */
    function pagination_links(array $pager, string $baseUrl = ''): string
    {
        $page     = (int) ($pager['page'] ?? 1);
        $lastPage = (int) ($pager['lastPage'] ?? 1);
        if ($lastPage <= 1) {
            return '';
        }

        $baseUrl = $baseUrl !== '' ? $baseUrl : Request::instance()->path();
        $build   = static function (int $target) use ($baseUrl): string {
            $query = $_GET;
            unset($query['r']);
            $query['page'] = $target;
            return url($baseUrl) . '?' . http_build_query($query);
        };

        $links = [];
        if ($page > 1) {
            $links[] = '<a class="page-link" href="' . e($build($page - 1)) . '" rel="prev" aria-label="قبلی">→</a>';
        }

        $window = 2;
        $start  = max(1, $page - $window);
        $end    = min($lastPage, $page + $window);

        if ($start > 1) {
            $links[] = '<a class="page-link" href="' . e($build(1)) . '">' . jdigits(1) . '</a>';
            if ($start > 2) {
                $links[] = '<span class="page-dots">…</span>';
            }
        }
        for ($i = $start; $i <= $end; $i++) {
            $links[] = $i === $page
                ? '<span class="page-link is-current" aria-current="page">' . jdigits($i) . '</span>'
                : '<a class="page-link" href="' . e($build($i)) . '">' . jdigits($i) . '</a>';
        }
        if ($end < $lastPage) {
            if ($end < $lastPage - 1) {
                $links[] = '<span class="page-dots">…</span>';
            }
            $links[] = '<a class="page-link" href="' . e($build($lastPage)) . '">' . jdigits($lastPage) . '</a>';
        }

        if ($page < $lastPage) {
            $links[] = '<a class="page-link" href="' . e($build($page + 1)) . '" rel="next" aria-label="بعدی">←</a>';
        }

        return '<nav class="pagination" aria-label="صفحه‌بندی">' . implode('', $links) . '</nav>';
    }
}

if (!function_exists('social_links')) {
    /** @return array<string,string> */
    function social_links(): array
    {
        $keys = ['instagram', 'telegram', 'linkedin', 'twitter', 'youtube', 'whatsapp', 'behance', 'aparats'];
        $out  = [];
        foreach ($keys as $key) {
            $value = (string) setting('social_' . $key, '');
            if ($value !== '') {
                $out[$key] = $value;
            }
        }
        return $out;
    }
}

if (!function_exists('social_label')) {
    function social_label(string $key): string
    {
        return [
            'instagram' => 'اینستاگرام',
            'telegram'  => 'تلگرام',
            'linkedin'  => 'لینکدین',
            'twitter'   => 'توییتر / ایکس',
            'youtube'   => 'یوتیوب',
            'whatsapp'  => 'واتس‌اپ',
            'behance'   => 'بیهنس',
            'aparats'   => 'آپارات',
        ][$key] ?? $key;
    }
}

if (!function_exists('icon_for_social')) {
    function icon_for_social(string $key): string
    {
        return [
            'instagram' => '◎',
            'telegram'  => '➤',
            'linkedin'  => 'in',
            'twitter'   => '𝕏',
            'youtube'   => '▶',
            'whatsapp'  => '☎',
            'behance'   => 'Bē',
            'aparats'   => '◉',
        ][$key] ?? '↗';
    }
}

if (!function_exists('array_get')) {
    function array_get(array $array, string $key, mixed $default = null): mixed
    {
        return $array[$key] ?? $default;
    }
}

if (!function_exists('json_ld')) {
    /** خروجی JSON-LD امن */
    function json_ld(array $data): string
    {
        return '<script type="application/ld+json">'
            . (string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP)
            . '</script>';
    }
}

if (!function_exists('phone_display')) {
    function phone_display(?string $phone): string
    {
        return Str::toPersianDigits((string) $phone);
    }
}

if (!function_exists('truncate_slug')) {
    function make_slug(string $value): string
    {
        return Str::slug($value);
    }
}

if (!function_exists('site_url')) {
    function site_url(string $path = '/'): string
    {
        $base = (string) Config::get('app.url', '');
        if ($base !== '') {
            return rtrim($base, '/') . '/' . ltrim($path, '/');
        }
        $request = Request::instance();
        return $request->fullUrl();
    }
}

if (!function_exists('menu_link_href')) {
    /**
     * ساخت آدرس نهایی یک آیتم منو.
     * آدرس‌های خارجی و mailto/tel دست‌نخورده می‌مانند.
     */
    function menu_link_href(string $href): string
    {
        $href = trim($href);
        if ($href === '') {
            return url('/');
        }
        if (preg_match('#^(https?:)?//#i', $href) === 1
            || str_starts_with($href, 'mailto:')
            || str_starts_with($href, 'tel:')) {
            return $href;
        }

        return url($href);
    }
}

if (!function_exists('menu_links')) {
    /**
     * رندر آیتم‌های منو به HTML.
     *
     * @param array<int,array<string,mixed>> $items خروجی MenuItem::tree()
     * @param string $mode desktop|mobile|submenu
     */
    function menu_links(array $items, string $mode = 'desktop'): string
    {
        $out = '';

        foreach ($items as $item) {
            $raw      = (string) ($item['href'] ?? '');
            $href     = menu_link_href($raw);
            $label    = e((string) ($item['label'] ?? ''));
            $target   = !empty($item['opens_new']) ? ' target="_blank" rel="noopener"' : '';
            $children = (array) ($item['children'] ?? []);
            $active   = active_class($raw);

            if ($mode === 'mobile') {
                $out .= '<a href="' . $href . '"' . $target . ' class="' . $active . '">' . $label . '</a>';
                if ($children !== []) {
                    $out .= '<div class="mobile-children">' . menu_links($children, 'mobile') . '</div>';
                }
                continue;
            }

            if ($children === []) {
                $out .= '<a href="' . $href . '"' . $target . ' class="' . $active . '">' . $label . '</a>';
                continue;
            }

            $out .= '<div class="nav-item has-sub">'
                . '<a href="' . $href . '"' . $target . ' class="' . $active . '">' . $label . '</a>'
                . '<div class="submenu">' . menu_links($children, 'submenu') . '</div>'
                . '</div>';
        }

        return $out;
    }
}

if (!function_exists('emphasize')) {
    /**
     * رندر متن با بخش تأکیدی.
     *
     * کل متن escape می‌شود و فقط عبارت‌های بین * با <em> رنگی نمایش داده می‌شوند؛
     * بنابراین مدیر می‌تواند بدون نوشتن HTML، تیتر را تأکید کند.
     */
    function emphasize(string $text): string
    {
        return (string) preg_replace('/\*(.+?)\*/u', '<em>$1</em>', e($text));
    }
}
