<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;
use App\Core\Model;
use App\Core\Str;

final class Service extends Model
{
    protected static string $table = 'services';
    protected static array $fillable = [
        'title', 'slug', 'label_en', 'icon', 'number', 'excerpt', 'body', 'items', 'image',
        'price_note', 'is_featured', 'is_published', 'sort_order', 'seo_title', 'seo_description',
        'created_at', 'updated_at',
    ];

    /** @return array<int,array<string,mixed>> */
    public static function published(bool $featuredOnly = false): array
    {
        $where = $featuredOnly ? 'is_published = 1 AND is_featured = 1' : 'is_published = 1';
        return DB::select(sprintf(
            'SELECT * FROM %s WHERE %s ORDER BY sort_order ASC, id ASC',
            DB::quoteIdent('services'),
            $where
        ));
    }

    /** @return array<int,array{id:int,title:string}> */
    public static function options(): array
    {
        return DB::select(sprintf(
            'SELECT id, title FROM %s ORDER BY sort_order ASC, title ASC',
            DB::quoteIdent('services')
        ));
    }

    /** @return array<string,mixed>|null */
    public static function publishedBySlug(string $slug): ?array
    {
        return DB::first(sprintf(
            'SELECT * FROM %s WHERE slug = ? AND is_published = 1 LIMIT 1',
            DB::quoteIdent('services')
        ), [$slug]);
    }

    /** تعداد نمونه‌کار هر خدمت */
    public static function worksCount(int $serviceId): int
    {
        return (int) DB::scalar(sprintf(
            'SELECT COUNT(*) FROM %s WHERE service_id = ? AND is_published = 1',
            DB::quoteIdent('works')
        ), [$serviceId]);
    }

    /** @return array<string,mixed> */
    public static function prepare(array $input, ?int $ignoreId = null): array
    {
        $title = trim((string) ($input['title'] ?? ''));
        $items = [];
        foreach ((array) ($input['items'] ?? []) as $item) {
            $item = trim((string) $item);
            if ($item !== '') {
                $items[] = $item;
            }
        }

        return [
            'title'             => $title,
            'slug'              => Str::uniqueSlug('services', trim((string) ($input['slug'] ?? '')) ?: $title, $ignoreId),
            'label_en'          => trim((string) ($input['label_en'] ?? '')) ?: null,
            'icon'              => trim((string) ($input['icon'] ?? '')) ?: '✦',
            'number'            => trim((string) ($input['number'] ?? '')) ?: null,
            'excerpt'           => trim((string) ($input['excerpt'] ?? '')) ?: null,
            'body'              => trim((string) ($input['body'] ?? '')) ?: null,
            'items'             => $items === [] ? null : (string) json_encode($items, JSON_UNESCAPED_UNICODE),
            'price_note'        => trim((string) ($input['price_note'] ?? '')) ?: null,
            'is_featured'       => (int) !empty($input['is_featured']),
            'is_published'      => (int) !empty($input['is_published']),
            'sort_order'        => (int) ($input['sort_order'] ?? 0),
            'seo_title'         => trim((string) ($input['seo_title'] ?? '')) ?: null,
            'seo_description'   => trim((string) ($input['seo_description'] ?? '')) ?: null,
            'updated_at'        => self::now(),
        ];
    }

    /** @return array<int,string> */
    public static function decodeItems(?string $json): array
    {
        if ($json === null || $json === '') {
            return [];
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? array_map('strval', $decoded) : [];
    }
}
