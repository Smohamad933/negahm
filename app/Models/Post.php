<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;
use App\Core\Model;
use App\Core\Str;

final class Post extends Model
{
    protected static string $table = 'posts';
    protected static array $fillable = [
        'title', 'slug', 'excerpt', 'body', 'cover', 'category_id', 'author_id', 'tags',
        'reading_time', 'is_featured', 'is_published', 'views', 'published_at',
        'seo_title', 'seo_description', 'created_at', 'updated_at',
    ];

    private const SELECT_LIST = 'p.*, c.title AS category_title, c.slug AS category_slug, u.name AS author_name';

    private static function fromClause(): string
    {
        return DB::quoteIdent('posts') . ' p'
            . ' LEFT JOIN ' . DB::quoteIdent('post_categories') . ' c ON c.id = p.category_id'
            . ' LEFT JOIN ' . DB::quoteIdent('users') . ' u ON u.id = p.author_id';
    }

    /** @return array{data:array,total:int,page:int,perPage:int,lastPage:int,from:int,to:int} */
    public static function publishedList(array $filters = [], int $perPage = 9, int $page = 1): array
    {
        $where  = ['p.is_published = 1'];
        $params = [];

        if (!empty($filters['category_id'])) {
            $where[]  = 'p.category_id = ?';
            $params[] = (int) $filters['category_id'];
        }
        if (!empty($filters['tag'])) {
            $where[]  = 'p.tags LIKE ?';
            $params[] = '%' . $filters['tag'] . '%';
        }
        if (!empty($filters['q'])) {
            $where[]  = '(p.title LIKE ? OR p.excerpt LIKE ? OR p.body LIKE ?)';
            $like     = '%' . $filters['q'] . '%';
            array_push($params, $like, $like, $like);
        }
        if (!empty($filters['featured'])) {
            $where[] = 'p.is_featured = 1';
        }

        $whereSql = implode(' AND ', $where);
        $total    = (int) DB::scalar('SELECT COUNT(*) FROM ' . self::fromClause() . ' WHERE ' . $whereSql, $params);
        $lastPage = max(1, (int) ceil($total / max(1, $perPage)));
        $page     = max(1, min($page, $lastPage));

        $rows = DB::select(sprintf(
            'SELECT %s FROM %s WHERE %s ORDER BY p.published_at DESC, p.id DESC LIMIT %d OFFSET %d',
            self::SELECT_LIST,
            self::fromClause(),
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

    /** @return array<int,array<string,mixed>> */
    public static function latest(int $limit = 3): array
    {
        return DB::select(sprintf(
            'SELECT %s FROM %s WHERE p.is_published = 1 ORDER BY p.published_at DESC, p.id DESC LIMIT %d',
            self::SELECT_LIST,
            self::fromClause(),
            $limit
        ));
    }

    /** @return array<string,mixed>|null */
    public static function publishedBySlug(string $slug): ?array
    {
        return DB::first(sprintf(
            'SELECT %s FROM %s WHERE p.slug = ? AND p.is_published = 1 LIMIT 1',
            self::SELECT_LIST,
            self::fromClause()
        ), [$slug]);
    }

    /** @return array<string,mixed>|null */
    public static function adminById(int $id): ?array
    {
        return DB::first(sprintf('SELECT %s FROM %s WHERE p.id = ? LIMIT 1', self::SELECT_LIST, self::fromClause()), [$id]);
    }

    /** @return array<int,array<string,mixed>> */
    public static function related(int $postId, ?int $categoryId, int $limit = 3): array
    {
        if (!$categoryId) {
            return DB::select(sprintf(
                'SELECT %s FROM %s WHERE p.is_published = 1 AND p.id <> ? ORDER BY p.id DESC LIMIT %d',
                self::SELECT_LIST,
                self::fromClause(),
                $limit
            ), [$postId]);
        }
        return DB::select(sprintf(
            'SELECT %s FROM %s WHERE p.is_published = 1 AND p.id <> ? AND p.category_id = ? ORDER BY p.id DESC LIMIT %d',
            self::SELECT_LIST,
            self::fromClause(),
            $limit
        ), [$postId, $categoryId]);
    }

    /** @return array<int,string> */
    public static function popularTags(int $limit = 15): array
    {
        $rows  = DB::select(sprintf('SELECT tags FROM %s WHERE is_published = 1 AND tags IS NOT NULL LIMIT 200', DB::quoteIdent('posts')));
        $count = [];
        foreach ($rows as $row) {
            foreach (Work::splitList((string) $row['tags']) as $tag) {
                $count[$tag] = ($count[$tag] ?? 0) + 1;
            }
        }
        arsort($count);
        return array_slice(array_keys($count), 0, $limit);
    }

    public static function increaseViews(int $id): void
    {
        DB::run(sprintf('UPDATE %s SET views = views + 1 WHERE id = ?', DB::quoteIdent('posts')), [$id]);
    }

    /** @param array<string,mixed> $input @return array<string,mixed> */
    public static function prepare(array $input, ?int $ignoreId = null, ?int $authorId = null): array
    {
        $title = trim((string) ($input['title'] ?? ''));
        $body  = (string) ($input['body'] ?? '');

        return [
            'title'           => $title,
            'slug'            => Str::uniqueSlug('posts', trim((string) ($input['slug'] ?? '')) ?: $title, $ignoreId),
            'excerpt'         => trim((string) ($input['excerpt'] ?? '')) ?: Str::excerpt($body, 200) ?: null,
            'body'            => $body !== '' ? $body : null,
            'category_id'     => !empty($input['category_id']) ? (int) $input['category_id'] : null,
            'author_id'       => $authorId,
            'tags'            => trim((string) ($input['tags'] ?? '')) ?: null,
            'reading_time'    => max(1, (int) ceil(mb_strlen(strip_tags($body)) / 1200)),
            'is_featured'     => (int) !empty($input['is_featured']),
            'is_published'    => (int) !empty($input['is_published']),
            'published_at'    => !empty($input['published_at']) ? date('Y-m-d H:i:s', (int) strtotime((string) $input['published_at'])) : self::now(),
            'seo_title'       => trim((string) ($input['seo_title'] ?? '')) ?: null,
            'seo_description' => trim((string) ($input['seo_description'] ?? '')) ?: null,
            'updated_at'      => self::now(),
        ];
    }
}
