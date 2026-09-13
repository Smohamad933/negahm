<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;
use App\Core\Model;

final class Testimonial extends Model
{
    protected static string $table = 'testimonials';
    protected static array $fillable = ['name', 'role', 'company', 'avatar', 'quote', 'rating', 'sort_order', 'is_published', 'created_at'];

    /** @return array<int,array<string,mixed>> */
    public static function published(int $limit = 12): array
    {
        return DB::select(sprintf(
            'SELECT * FROM %s WHERE is_published = 1 ORDER BY sort_order ASC, id DESC LIMIT %d',
            DB::quoteIdent('testimonials'),
            $limit
        ));
    }

    /** @return array<string,mixed> */
    public static function prepare(array $input): array
    {
        $rating = (int) ($input['rating'] ?? 5);
        return [
            'name'         => trim((string) ($input['name'] ?? '')),
            'role'         => trim((string) ($input['role'] ?? '')) ?: null,
            'company'      => trim((string) ($input['company'] ?? '')) ?: null,
            'quote'        => trim((string) ($input['quote'] ?? '')),
            'rating'       => max(1, min(5, $rating)),
            'sort_order'   => (int) ($input['sort_order'] ?? 0),
            'is_published' => (int) !empty($input['is_published']),
        ];
    }
}
