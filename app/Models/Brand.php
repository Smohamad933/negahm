<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;
use App\Core\Model;
use App\Core\Str;

final class Brand extends Model
{
    protected static string $table = 'brands';
    protected static array $fillable = [
        'name', 'name_en', 'slug', 'category_id', 'logo', 'cover', 'excerpt', 'body',
        'website', 'location', 'industry', 'started_at', 'tags', 'socials', 'video_url',
        'is_featured', 'show_in_marquee', 'is_published', 'has_dedicated_page', 'sort_order',
        'seo_title', 'seo_description', 'created_at', 'updated_at',
    ];

    private const PUBLISHED_SELECT = 'b.*, c.title AS category_title, c.slug AS category_slug,
        (SELECT COUNT(*) FROM works w WHERE w.brand_id = b.id AND w.is_published = 1) AS works_count';

    /** برندهای منتشرشده با دسته‌بندی */
    public static function publishedList(?int $categoryId = null, string $search = '', int $perPage = 12, int $page = 1, string $orderBy = 'b.sort_order ASC, b.id DESC'): array
    {
        $where  = ['b.is_published = 1'];
        $params = [];

        if ($categoryId !== null && $categoryId > 0) {
            $where[]  = 'b.category_id = ?';
            $params[] = $categoryId;
        }
        if ($search !== '') {
            $where[]  = '(b.name LIKE ? OR b.name_en LIKE ? OR b.industry LIKE ? OR b.tags LIKE ?)';
            $like     = '%' . $search . '%';
            array_push($params, $like, $like, $like, $like);
        }

        $whereSql = implode(' AND ', $where);
        $table    = DB::quoteIdent('brands') . ' b LEFT JOIN ' . DB::quoteIdent('brand_categories') . ' c ON c.id = b.category_id';

        $total    = (int) DB::scalar(sprintf('SELECT COUNT(*) FROM %1$s b LEFT JOIN %2$s c ON c.id = b.category_id WHERE %3$s', DB::quoteIdent('brands'), DB::quoteIdent('brand_categories'), $whereSql), $params);
        $lastPage = max(1, (int) ceil($total / max(1, $perPage)));
        $page     = max(1, min($page, $lastPage));

        $rows = DB::select(sprintf(
            'SELECT %s FROM %s WHERE %s ORDER BY %s LIMIT %d OFFSET %d',
            self::PUBLISHED_SELECT,
            $table,
            $whereSql,
            self::sanitizeOrder($orderBy),
            $perPage,
            ($page - 1) * $perPage
        ), $params);

        return [
            'data'     => $rows,
            'total'    => $total,
            'page'     => $page,
            'perPage'  => $perPage,
            'lastPage' => $lastPage,
            'from'     => $total === 0 ? 0 : ($page - 1) * $perPage + 1,
            'to'       => ($page - 1) * $perPage + count($rows),
        ];
    }

    /** @return array<int,array<string,mixed>> */
    public static function featured(int $limit = 12): array
    {
        return DB::select(sprintf(
            'SELECT %s FROM %s WHERE b.is_published = 1 AND b.is_featured = 1 ORDER BY b.sort_order ASC, b.id DESC LIMIT %d',
            self::PUBLISHED_SELECT,
            DB::quoteIdent('brands') . ' b LEFT JOIN ' . DB::quoteIdent('brand_categories') . ' c ON c.id = b.category_id',
            $limit
        ));
    }

    /** نام برندها برای نوار متحرک صفحه اصلی */
    public static function marquee(int $limit = 60): array
    {
        return DB::select(sprintf(
            'SELECT id, name, name_en, logo, slug FROM %s
             WHERE is_published = 1 AND show_in_marquee = 1
             ORDER BY sort_order ASC, id ASC LIMIT %d',
            DB::quoteIdent('brands'),
            $limit
        ));
    }

    /** @return array<string,mixed>|null */
    public static function publishedBySlug(string $slug): ?array
    {
        return DB::first(sprintf(
            'SELECT %s FROM %s WHERE b.slug = ? AND b.is_published = 1 LIMIT 1',
            self::PUBLISHED_SELECT,
            DB::quoteIdent('brands') . ' b LEFT JOIN ' . DB::quoteIdent('brand_categories') . ' c ON c.id = b.category_id'
        ), [$slug]);
    }

    /** @return array<string,mixed>|null */
    public static function adminById(int $id): ?array
    {
        return DB::first(sprintf(
            'SELECT %s FROM %s WHERE b.id = ? LIMIT 1',
            self::PUBLISHED_SELECT,
            DB::quoteIdent('brands') . ' b LEFT JOIN ' . DB::quoteIdent('brand_categories') . ' c ON c.id = b.category_id'
        ), [$id]);
    }

    /** @return array<int,array<string,mixed>> */
    public static function adminList(string $search = '', int $perPage = 15, int $page = 1): array
    {
        $where  = ['1=1'];
        $params = [];
        if ($search !== '') {
            $where[]  = '(b.name LIKE ? OR b.name_en LIKE ? OR b.slug LIKE ?)';
            $like     = '%' . $search . '%';
            array_push($params, $like, $like, $like);
        }
        $whereSql = implode(' AND ', $where);

        $total    = (int) DB::scalar(sprintf('SELECT COUNT(*) FROM %s b WHERE %s', DB::quoteIdent('brands'), $whereSql), $params);
        $lastPage = max(1, (int) ceil($total / max(1, $perPage)));
        $page     = max(1, min($page, $lastPage));

        $rows = DB::select(sprintf(
            'SELECT %s FROM %s WHERE %s ORDER BY b.sort_order ASC, b.id DESC LIMIT %d OFFSET %d',
            self::PUBLISHED_SELECT,
            DB::quoteIdent('brands') . ' b LEFT JOIN ' . DB::quoteIdent('brand_categories') . ' c ON c.id = b.category_id',
            $whereSql,
            $perPage,
            ($page - 1) * $perPage
        ), $params);

        return [
            'data' => $rows, 'total' => $total, 'page' => $page, 'perPage' => $perPage,
            'lastPage' => $lastPage, 'from' => $total === 0 ? 0 : ($page - 1) * $perPage + 1,
            'to' => ($page - 1) * $perPage + count($rows),
        ];
    }

    public static function increaseViews(int $id): void
    {
        DB::run(sprintf('UPDATE %s SET views = views + 1 WHERE id = ?', DB::quoteIdent('brands')), [$id]);
    }

    /** برندهای هم‌دسته برای پیشنهاد در صفحه برند */
    public static function related(int $brandId, ?int $categoryId, int $limit = 4): array
    {
        if ($categoryId === null || $categoryId === 0) {
            return DB::select(sprintf(
                'SELECT * FROM %s WHERE is_published = 1 AND id <> ? ORDER BY is_featured DESC, id DESC LIMIT %d',
                DB::quoteIdent('brands'),
                $limit
            ), [$brandId]);
        }
        return DB::select(sprintf(
            'SELECT * FROM %s WHERE is_published = 1 AND id <> ? AND category_id = ? ORDER BY sort_order ASC, id DESC LIMIT %d',
            DB::quoteIdent('brands'),
            $limit
        ), [$brandId, $categoryId]);
    }

    /**
     * ساخت دیتای آماده ذخیره از ورودی فرم
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public static function prepare(array $input, ?int $ignoreId = null): array
    {
        $socials = [];
        foreach ((array) ($input['socials'] ?? []) as $key => $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                $socials[$key] = $value;
            }
        }

        $name = trim((string) ($input['name'] ?? ''));

        return [
            'name'               => $name,
            'name_en'            => trim((string) ($input['name_en'] ?? '')) ?: null,
            'slug'               => Str::uniqueSlug('brands', trim((string) ($input['slug'] ?? '')) ?: $name, $ignoreId),
            'category_id'        => !empty($input['category_id']) ? (int) $input['category_id'] : null,
            'excerpt'            => trim((string) ($input['excerpt'] ?? '')) ?: null,
            'body'               => trim((string) ($input['body'] ?? '')) ?: null,
            'website'            => trim((string) ($input['website'] ?? '')) ?: null,
            'location'           => trim((string) ($input['location'] ?? '')) ?: null,
            'industry'           => trim((string) ($input['industry'] ?? '')) ?: null,
            'started_at'         => trim((string) ($input['started_at'] ?? '')) ?: null,
            'tags'               => trim((string) ($input['tags'] ?? '')) ?: null,
            'socials'            => $socials === [] ? null : (string) json_encode($socials, JSON_UNESCAPED_UNICODE),
            'video_url'          => trim((string) ($input['video_url'] ?? '')) ?: null,
            'is_featured'        => (int) !empty($input['is_featured']),
            'show_in_marquee'    => (int) !empty($input['show_in_marquee']),
            'is_published'       => (int) !empty($input['is_published']),
            'has_dedicated_page' => (int) !empty($input['has_dedicated_page']),
            'sort_order'         => (int) ($input['sort_order'] ?? 0),
            'seo_title'          => trim((string) ($input['seo_title'] ?? '')) ?: null,
            'seo_description'    => trim((string) ($input['seo_description'] ?? '')) ?: null,
            'updated_at'         => self::now(),
        ];
    }

    /** @return array<string,string> */
    public static function decodeSocials(?string $json): array
    {
        if ($json === null || $json === '') {
            return [];
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }
}
