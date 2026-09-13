<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;
use App\Core\Model;
use App\Core\Str;

final class Page extends Model
{
    protected static string $table = 'pages';
    protected static array $fillable = [
        'title', 'slug', 'subtitle', 'body', 'cover', 'template', 'show_in_menu',
        'show_in_footer', 'is_published', 'sort_order', 'seo_title', 'seo_description',
        'created_at', 'updated_at',
    ];

    /** @return array<int,array<string,mixed>> */
    public static function published(): array
    {
        return DB::select(sprintf(
            'SELECT * FROM %s WHERE is_published = 1 ORDER BY sort_order ASC, id ASC',
            DB::quoteIdent('pages')
        ));
    }

    /** @return array<int,array<string,mixed>> */
    public static function forMenu(): array
    {
        return DB::select(sprintf(
            'SELECT * FROM %s WHERE is_published = 1 AND show_in_menu = 1 ORDER BY sort_order ASC, id ASC',
            DB::quoteIdent('pages')
        ));
    }

    /** @return array<int,array<string,mixed>> */
    public static function forFooter(): array
    {
        return DB::select(sprintf(
            'SELECT * FROM %s WHERE is_published = 1 AND show_in_footer = 1 ORDER BY sort_order ASC, id ASC',
            DB::quoteIdent('pages')
        ));
    }

    /** @return array<string,mixed>|null */
    public static function publishedBySlug(string $slug): ?array
    {
        return DB::first(sprintf(
            'SELECT * FROM %s WHERE slug = ? AND is_published = 1 LIMIT 1',
            DB::quoteIdent('pages')
        ), [$slug]);
    }

    /** @return array<string,mixed> */
    public static function prepare(array $input, ?int $ignoreId = null): array
    {
        $title = trim((string) ($input['title'] ?? ''));
        return [
            'title'           => $title,
            'slug'            => Str::uniqueSlug('pages', trim((string) ($input['slug'] ?? '')) ?: $title, $ignoreId),
            'subtitle'        => trim((string) ($input['subtitle'] ?? '')) ?: null,
            'body'            => trim((string) ($input['body'] ?? '')) ?: null,
            'template'        => in_array((string) ($input['template'] ?? ''), ['default', 'full', 'sidebar'], true) ? (string) $input['template'] : 'default',
            'show_in_menu'    => (int) !empty($input['show_in_menu']),
            'show_in_footer'  => (int) !empty($input['show_in_footer']),
            'is_published'    => (int) !empty($input['is_published']),
            'sort_order'      => (int) ($input['sort_order'] ?? 0),
            'seo_title'       => trim((string) ($input['seo_title'] ?? '')) ?: null,
            'seo_description' => trim((string) ($input['seo_description'] ?? '')) ?: null,
            'updated_at'      => self::now(),
        ];
    }
}
