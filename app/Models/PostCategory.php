<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;
use App\Core\Model;

final class PostCategory extends Model
{
    protected static string $table = 'post_categories';
    protected static array $fillable = ['title', 'slug', 'description', 'sort_order', 'created_at'];

    /** @return array<int,array<string,mixed>> */
    public static function withCounts(): array
    {
        return DB::select(sprintf(
            'SELECT c.*, COUNT(p.id) AS posts_count
             FROM %1$s c
             LEFT JOIN %2$s p ON p.category_id = c.id AND p.is_published = 1
             GROUP BY c.id
             ORDER BY c.sort_order ASC, c.title ASC',
            DB::quoteIdent('post_categories'),
            DB::quoteIdent('posts')
        ));
    }

    /** @return array<int,array{id:int,title:string}> */
    public static function options(): array
    {
        return DB::select(sprintf(
            'SELECT id, title FROM %s ORDER BY sort_order ASC, title ASC',
            DB::quoteIdent('post_categories')
        ));
    }

    /** @return array<string,mixed>|null */
    public static function bySlug(string $slug): ?array
    {
        return DB::first(sprintf('SELECT * FROM %s WHERE slug = ? LIMIT 1', DB::quoteIdent('post_categories')), [$slug]);
    }
}
