<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;
use App\Core\Model;

final class WorkCategory extends Model
{
    protected static string $table = 'work_categories';
    protected static array $fillable = ['title', 'slug', 'label_en', 'description', 'sort_order', 'is_published', 'created_at'];

    /** @return array<int,array<string,mixed>> */
    public static function published(): array
    {
        return DB::select(sprintf(
            'SELECT c.*, COUNT(w.id) AS works_count
             FROM %1$s c
             LEFT JOIN %2$s w ON w.category_id = c.id AND w.is_published = 1
             WHERE c.is_published = 1
             GROUP BY c.id
             ORDER BY c.sort_order ASC, c.title ASC',
            DB::quoteIdent('work_categories'),
            DB::quoteIdent('works')
        ));
    }

    /** @return array<int,array{id:int,title:string}> */
    public static function options(): array
    {
        return DB::select(sprintf(
            'SELECT id, title FROM %s ORDER BY sort_order ASC, title ASC',
            DB::quoteIdent('work_categories')
        ));
    }

    /** @return array<string,mixed>|null */
    public static function publishedBySlug(string $slug): ?array
    {
        return DB::first(sprintf(
            'SELECT * FROM %s WHERE slug = ? AND is_published = 1 LIMIT 1',
            DB::quoteIdent('work_categories')
        ), [$slug]);
    }
}
