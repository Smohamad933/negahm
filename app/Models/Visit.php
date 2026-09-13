<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;
use App\Core\Model;
use App\Core\Request;

final class Visit extends Model
{
    protected static string $table = 'visit_logs';
    protected static array $fillable = ['path', 'ip', 'referrer', 'user_agent', 'visited_on', 'created_at'];

    /** ثبت بازدید (حداکثر یک بار در هر ۳۰ دقیقه برای هر IP و مسیر) */
    public static function track(string $path): void
    {
        $request = Request::instance();
        $ip      = $request->ip();

        if (str_contains(strtolower($request->userAgent()), 'bot')) {
            return;
        }

        $recent = DB::scalar(sprintf(
            'SELECT COUNT(*) FROM %s WHERE ip = ? AND path = ? AND created_at > ?',
            DB::quoteIdent('visit_logs')
        ), [$ip, $path, date('Y-m-d H:i:s', strtotime('-30 minutes'))]);

        if ((int) $recent > 0) {
            return;
        }

        self::create([
            'path'       => mb_substr($path, 0, 255),
            'ip'         => $ip,
            'referrer'   => mb_substr((string) ($_SERVER['HTTP_REFERER'] ?? ''), 0, 255) ?: null,
            'user_agent' => mb_substr($request->userAgent(), 0, 255) ?: null,
            'visited_on' => date('Y-m-d'),
            'created_at' => self::now(),
        ]);
    }

    public static function todayCount(): int
    {
        return (int) DB::scalar(sprintf(
            'SELECT COUNT(*) FROM %s WHERE visited_on = ?',
            DB::quoteIdent('visit_logs')
        ), [date('Y-m-d')]);
    }

    public static function totalCount(): int
    {
        return self::count();
    }

    /** @return array<string,int> */
    public static function dailyCounts(int $days = 14): array
    {
        $rows = DB::select(sprintf(
            'SELECT visited_on AS d, COUNT(*) AS c FROM %s WHERE visited_on >= ? GROUP BY visited_on ORDER BY visited_on ASC',
            DB::quoteIdent('visit_logs')
        ), [date('Y-m-d', strtotime('-' . ($days - 1) . ' days'))]);

        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row['d']] = (int) $row['c'];
        }

        $out = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date       = date('Y-m-d', strtotime('-' . $i . ' days'));
            $out[$date] = $map[$date] ?? 0;
        }
        return $out;
    }

    /** @return array<int,array<string,mixed>> */
    public static function topPaths(int $days = 30, int $limit = 8): array
    {
        return DB::select(sprintf(
            'SELECT path, COUNT(*) AS visits FROM %s WHERE visited_on >= ? GROUP BY path ORDER BY visits DESC LIMIT %d',
            DB::quoteIdent('visit_logs'),
            $limit
        ), [date('Y-m-d', strtotime('-' . ($days - 1) . ' days'))]);
    }

    /**
     * آمار بازدید به تفکیک صفحه، با صفحه‌بندی.
     *
     * @return array<string,mixed>
     */
    public static function pageStats(int $days = 30, int $perPage = 20, int $page = 1): array
    {
        $perPage = max(1, $perPage);
        $page    = max(1, $page);
        $since   = date('Y-m-d', strtotime('-' . max(1, $days) . ' days'));

        $total = (int) DB::scalar(sprintf(
            'SELECT COUNT(*) FROM (SELECT path FROM %s WHERE visited_on >= ? GROUP BY path) AS t',
            DB::quoteIdent('visit_logs')
        ), [$since]);

        $lastPage = max(1, (int) ceil($total / $perPage));
        $page     = min($page, $lastPage);
        $offset   = ($page - 1) * $perPage;

        $rows = DB::select(sprintf(
            'SELECT path, COUNT(*) AS visits, MAX(visited_on) AS last_seen
             FROM %s WHERE visited_on >= ?
             GROUP BY path ORDER BY visits DESC, path ASC LIMIT %d OFFSET %d',
            DB::quoteIdent('visit_logs'),
            $perPage,
            $offset
        ), [$since]);

        foreach ($rows as &$row) {
            $row['title'] = self::titleFor((string) $row['path']);
        }
        unset($row);

        return [
            'data'    => $rows,
            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
            'from'    => $total === 0 ? 0 : $offset + 1,
            'to'      => min($total, $offset + $perPage),
        ];
    }

    /** مجموع بازدیدها در بازه‌ی روزهای گذشته */
    public static function totalInRange(int $days = 30): int
    {
        return (int) DB::scalar(sprintf(
            'SELECT COUNT(*) FROM %s WHERE visited_on >= ?',
            DB::quoteIdent('visit_logs')
        ), [date('Y-m-d', strtotime('-' . max(1, $days) . ' days'))]);
    }

    /** تعداد IPهای یکتا در بازه‌ی روزهای گذشته */
    public static function uniqueVisitors(int $days = 30): int
    {
        return (int) DB::scalar(sprintf(
            'SELECT COUNT(DISTINCT ip) FROM %s WHERE visited_on >= ?',
            DB::quoteIdent('visit_logs')
        ), [date('Y-m-d', strtotime('-' . max(1, $days) . ' days'))]);
    }

    /**
     * حدس عنوان یک صفحه از روی آدرسش (برای نمایش در جدول آمار).
     *
     * @var array<string,string> $cache
     */
    public static function titleFor(string $path): string
    {
        static $cache = [];
        if (isset($cache[$path])) {
            return $cache[$path];
        }

        $title = self::lookupTitle($path);

        return $cache[$path] = $title;
    }

    private static function lookupTitle(string $path): string
    {
        $fixed = [
            '/'        => 'خانه',
            '/services' => 'خدمات',
            '/works'   => 'نمونه‌کارها',
            '/brands'  => 'برندها و مشتریان',
            '/blog'    => 'بلاگ',
            '/about'   => 'درباره ما',
            '/contact' => 'تماس با ما',
            '/faq'     => 'پرسش‌های متداول',
            '/search'  => 'جست‌وجو',
        ];

        $clean = '/' . trim(explode('?', $path)[0], '/');
        if (isset($fixed['/' . trim($clean, '/')])) {
            return $fixed['/' . trim($clean, '/')];
        }

        $patterns = [
            '#^/services/([^/]+)$'          => ['services', 'title'],
            '#^/works/category/([^/]+)$'    => ['work_categories', 'title'],
            '#^/works/([^/]+)$'             => ['works', 'title'],
            '#^/brands/([^/]+)$'            => ['brands', 'name'],
            '#^/blog/category/([^/]+)$'     => ['post_categories', 'title'],
            '#^/blog/([^/]+)$'              => ['posts', 'title'],
            '#^/p/([^/]+)$'                 => ['pages', 'title'],
        ];

        foreach ($patterns as $pattern => [$table, $column]) {
            if (preg_match($pattern, $clean, $m) === 1) {
                $row = DB::first(sprintf(
                    'SELECT %s FROM %s WHERE slug = ?',
                    DB::quoteIdent($column),
                    DB::quoteIdent($table)
                ), [rawurldecode((string) $m[1])]);
                if ($row !== null && trim((string) $row[$column]) !== '') {
                    return (string) $row[$column];
                }
                break;
            }
        }

        return $clean;
    }

    public static function prune(int $days = 180): int
    {
        return DB::run(sprintf('DELETE FROM %s WHERE visited_on < ?', DB::quoteIdent('visit_logs')), [
            date('Y-m-d', strtotime('-' . $days . ' days')),
        ])->rowCount();
    }
}
