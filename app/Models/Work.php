<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;
use App\Core\Model;
use App\Core\Str;

final class Work extends Model
{
    protected static string $table = 'works';
    protected static array $fillable = [
        'title', 'slug', 'label_en', 'brand_id', 'category_id', 'service_id', 'cover', 'excerpt', 'body',
        'challenge', 'solution', 'result', 'client_name', 'year', 'duration', 'services_list',
        'video_url', 'link_url', 'tags', 'is_featured', 'is_published', 'sort_order',
        'published_at', 'seo_title', 'seo_description', 'created_at', 'updated_at',
    ];

    private const SELECT_LIST = 'w.*, b.name AS brand_name, b.slug AS brand_slug, b.logo AS brand_logo,
        wc.title AS category_title, wc.slug AS category_slug, wc.label_en AS category_label';

    private static function fromClause(): string
    {
        return DB::quoteIdent('works') . ' w'
            . ' LEFT JOIN ' . DB::quoteIdent('brands') . ' b ON b.id = w.brand_id'
            . ' LEFT JOIN ' . DB::quoteIdent('work_categories') . ' wc ON wc.id = w.category_id';
    }

    /**
     * فهرست صفحه‌بندی‌شده نمونه‌کارها
     * @return array{data:array,total:int,page:int,perPage:int,lastPage:int,from:int,to:int}
     */
    public static function publishedList(array $filters = [], int $perPage = 9, int $page = 1): array
    {
        [$where, $params] = self::buildWhere($filters, true);
        $whereSql = implode(' AND ', $where);

        $total    = (int) DB::scalar('SELECT COUNT(*) FROM ' . self::fromClause() . ' WHERE ' . $whereSql, $params);
        $lastPage = max(1, (int) ceil($total / max(1, $perPage)));
        $page     = max(1, min($page, $lastPage));

        $orderBy = self::sanitizeOrder((string) ($filters['order'] ?? 'w.sort_order ASC, w.id DESC'));

        $rows = DB::select(sprintf(
            'SELECT %s FROM %s WHERE %s ORDER BY %s LIMIT %d OFFSET %d',
            self::SELECT_LIST,
            self::fromClause(),
            $whereSql,
            $orderBy,
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

    /**
     * @param array<string,mixed> $filters
     * @return array{0:array<int,string>,1:array<int,mixed>}
     */
    private static function buildWhere(array $filters, bool $publishedOnly): array
    {
        $where  = [];
        $params = [];

        if ($publishedOnly) {
            $where[] = 'w.is_published = 1';
        }
        if (!empty($filters['brand_id'])) {
            $where[]  = 'w.brand_id = ?';
            $params[] = (int) $filters['brand_id'];
        }
        if (!empty($filters['category_id'])) {
            $where[]  = 'w.category_id = ?';
            $params[] = (int) $filters['category_id'];
        }
        if (!empty($filters['service_id'])) {
            $where[]  = 'w.service_id = ?';
            $params[] = (int) $filters['service_id'];
        }
        if (!empty($filters['featured'])) {
            $where[] = 'w.is_featured = 1';
        }
        if (!empty($filters['q'])) {
            $where[]  = '(w.title LIKE ? OR w.excerpt LIKE ? OR w.tags LIKE ? OR b.name LIKE ?)';
            $like     = '%' . $filters['q'] . '%';
            array_push($params, $like, $like, $like, $like);
        }

        if ($where === []) {
            $where[] = '1=1';
        }

        return [$where, $params];
    }

    /** @return array<int,array<string,mixed>> */
    public static function featured(int $limit = 6): array
    {
        return DB::select(sprintf(
            'SELECT %s FROM %s WHERE w.is_published = 1 AND w.is_featured = 1 ORDER BY w.sort_order ASC, w.id DESC LIMIT %d',
            self::SELECT_LIST,
            self::fromClause(),
            $limit
        ));
    }

    /** @return array<int,array<string,mixed>> */
    public static function latest(int $limit = 6, bool $publishedOnly = true): array
    {
        $where = $publishedOnly ? 'w.is_published = 1' : '1=1';
        return DB::select(sprintf(
            'SELECT %s FROM %s WHERE %s ORDER BY w.id DESC LIMIT %d',
            self::SELECT_LIST,
            self::fromClause(),
            $where,
            $limit
        ));
    }

    /** @return array<string,mixed>|null */
    public static function publishedBySlug(string $slug): ?array
    {
        return DB::first(sprintf(
            'SELECT %s FROM %s WHERE w.slug = ? AND w.is_published = 1 LIMIT 1',
            self::SELECT_LIST,
            self::fromClause()
        ), [$slug]);
    }

    /** @return array<string,mixed>|null */
    public static function adminById(int $id): ?array
    {
        return DB::first(sprintf(
            'SELECT %s FROM %s WHERE w.id = ? LIMIT 1',
            self::SELECT_LIST,
            self::fromClause()
        ), [$id]);
    }

    /** نمونه‌کارهای مرتبط (هم‌دسته یا هم‌برند) */
    public static function related(int $workId, ?int $categoryId, ?int $brandId, int $limit = 3): array
    {
        $sql = sprintf('SELECT %s FROM %s WHERE w.is_published = 1 AND w.id <> ? AND (', self::SELECT_LIST, self::fromClause());
        $parts  = [];
        $params = [$workId];

        if ($categoryId) {
            $parts[]  = 'w.category_id = ?';
            $params[] = $categoryId;
        }
        if ($brandId) {
            $parts[]  = 'w.brand_id = ?';
            $params[] = $brandId;
        }
        if ($parts === []) {
            $parts[] = '1=1';
        }

        return DB::select($sql . implode(' OR ', $parts) . sprintf(') ORDER BY w.id DESC LIMIT %d', $limit), $params);
    }

    /** پروژه قبلی/بعدی برای ناوبری */
    public static function neighbours(int $id): array
    {
        $prev = DB::first(sprintf(
            'SELECT %s FROM %s WHERE w.is_published = 1 AND w.id < ? ORDER BY w.id DESC LIMIT 1',
            self::SELECT_LIST,
            self::fromClause()
        ), [$id]);
        $next = DB::first(sprintf(
            'SELECT %s FROM %s WHERE w.is_published = 1 AND w.id > ? ORDER BY w.id ASC LIMIT 1',
            self::SELECT_LIST,
            self::fromClause()
        ), [$id]);

        return ['prev' => $prev, 'next' => $next];
    }

    public static function increaseViews(int $id): void
    {
        DB::run(sprintf('UPDATE %s SET views = views + 1 WHERE id = ?', DB::quoteIdent('works')), [$id]);
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public static function prepare(array $input, ?int $ignoreId = null): array
    {
        $title = trim((string) ($input['title'] ?? ''));

        return [
            'title'         => $title,
            'slug'          => Str::uniqueSlug('works', trim((string) ($input['slug'] ?? '')) ?: $title, $ignoreId),
            'label_en'      => trim((string) ($input['label_en'] ?? '')) ?: null,
            'brand_id'      => !empty($input['brand_id']) ? (int) $input['brand_id'] : null,
            'category_id'   => !empty($input['category_id']) ? (int) $input['category_id'] : null,
            'service_id'    => !empty($input['service_id']) ? (int) $input['service_id'] : null,
            'excerpt'       => trim((string) ($input['excerpt'] ?? '')) ?: null,
            'body'          => trim((string) ($input['body'] ?? '')) ?: null,
            'challenge'     => trim((string) ($input['challenge'] ?? '')) ?: null,
            'solution'      => trim((string) ($input['solution'] ?? '')) ?: null,
            'result'        => trim((string) ($input['result'] ?? '')) ?: null,
            'client_name'   => trim((string) ($input['client_name'] ?? '')) ?: null,
            'year'          => trim((string) ($input['year'] ?? '')) ?: null,
            'duration'      => trim((string) ($input['duration'] ?? '')) ?: null,
            'services_list' => trim((string) ($input['services_list'] ?? '')) ?: null,
            'video_url'     => trim((string) ($input['video_url'] ?? '')) ?: null,
            'link_url'      => trim((string) ($input['link_url'] ?? '')) ?: null,
            'tags'          => trim((string) ($input['tags'] ?? '')) ?: null,
            'is_featured'   => (int) !empty($input['is_featured']),
            'is_published'  => (int) !empty($input['is_published']),
            'sort_order'    => (int) ($input['sort_order'] ?? 0),
            'published_at'  => !empty($input['published_at']) ? date('Y-m-d H:i:s', (int) strtotime((string) $input['published_at'])) : self::now(),
            'seo_title'     => trim((string) ($input['seo_title'] ?? '')) ?: null,
            'seo_description' => trim((string) ($input['seo_description'] ?? '')) ?: null,
            'updated_at'    => self::now(),
        ];
    }

    /** @return array<int,string> */
    public static function splitList(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }
        $parts = preg_split('/[,،\n]+/u', $value) ?: [];
        return array_values(array_filter(array_map('trim', $parts), static fn ($v) => $v !== ''));
    }
}
