<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;
use App\Core\Model;

final class ProcessStep extends Model
{
    protected static string $table = 'process_steps';
    protected static array $fillable = ['title', 'body', 'number', 'sort_order', 'is_published'];

    /** @return array<int,array<string,mixed>> */
    public static function published(): array
    {
        return DB::select(sprintf(
            'SELECT * FROM %s WHERE is_published = 1 ORDER BY sort_order ASC, id ASC',
            DB::quoteIdent('process_steps')
        ));
    }

    /** @return array<string,mixed> */
    public static function prepare(array $input): array
    {
        return [
            'title'        => trim((string) ($input['title'] ?? '')),
            'body'         => trim((string) ($input['body'] ?? '')) ?: null,
            'number'       => trim((string) ($input['number'] ?? '')) ?: null,
            'sort_order'   => (int) ($input['sort_order'] ?? 0),
            'is_published' => (int) !empty($input['is_published']),
        ];
    }
}
