<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;
use App\Core\Model;

final class Slider extends Model
{
    protected static string $table = 'sliders';
    protected static array $fillable = ['title', 'subtitle', 'image', 'link', 'button_text', 'sort_order', 'is_active', 'created_at'];

    /** @return array<int,array<string,mixed>> */
    public static function active(): array
    {
        return DB::select(sprintf(
            'SELECT * FROM %s WHERE is_active = 1 ORDER BY sort_order ASC, id ASC',
            DB::quoteIdent('sliders')
        ));
    }

    /** @return array<string,mixed> */
    public static function prepare(array $input): array
    {
        return [
            'title'       => trim((string) ($input['title'] ?? '')) ?: null,
            'subtitle'    => trim((string) ($input['subtitle'] ?? '')) ?: null,
            'link'        => trim((string) ($input['link'] ?? '')) ?: null,
            'button_text' => trim((string) ($input['button_text'] ?? '')) ?: null,
            'sort_order'  => (int) ($input['sort_order'] ?? 0),
            'is_active'   => (int) !empty($input['is_active']),
        ];
    }
}
