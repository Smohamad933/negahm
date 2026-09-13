<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;
use App\Core\Model;

final class BrandCategory extends Model
{
    protected static string $table = 'brand_categories';
    protected static array $fillable = ['title', 'slug', 'description', 'sort_order', 'is_published', 'created_at'];

    /** @return array<int,array<string,mixed>> */
    public static function published(): array
    {
        return DB::select(sprintf(
            'SELECT c.*, COUNT(b.id) AS brands_count
             FROM %1$s c
             LEFT JOIN %2$s b ON b.category_id = c.id AND b.is_published = 1
             WHERE c.is_published = 1
             GROUP BY c.id
             ORDER BY c.sort_order ASC, c.title ASC',
            DB::quoteIdent('brand_categories'),
            DB::quoteIdent('brands')
        ));
    }

    /** @return array<int,array{id:int,title:string}> فهرست ساده برای select */
    public static function options(): array
    {
        return DB::select(sprintf(
            'SELECT id, title FROM %s ORDER BY sort_order ASC, title ASC',
            DB::quoteIdent('brand_categories')
        ));
    }
}
