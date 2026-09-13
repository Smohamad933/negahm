<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;
use App\Core\Model;

final class Stat extends Model
{
    protected static string $table = 'stats';
    protected static array $fillable = ['label', 'value', 'suffix', 'description', 'sort_order', 'is_published'];

    /** @return array<int,array<string,mixed>> */
    public static function published(): array
    {
        return DB::select(sprintf(
            'SELECT * FROM %s WHERE is_published = 1 ORDER BY sort_order ASC, id ASC',
            DB::quoteIdent('stats')
        ));
    }

    /** @return array<string,mixed> */
    public static function prepare(array $input): array
    {
        return [
            'label'        => trim((string) ($input['label'] ?? '')),
            'value'        => trim((string) ($input['value'] ?? '')),
            'suffix'       => trim((string) ($input['suffix'] ?? '')) ?: null,
            'description'  => trim((string) ($input['description'] ?? '')) ?: null,
            'sort_order'   => (int) ($input['sort_order'] ?? 0),
            'is_published' => (int) !empty($input['is_published']),
        ];
    }
}
