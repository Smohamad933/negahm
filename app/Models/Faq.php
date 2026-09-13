<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;
use App\Core\Model;

final class Faq extends Model
{
    protected static string $table = 'faqs';
    protected static array $fillable = ['question', 'answer', 'group', 'sort_order', 'is_published', 'created_at'];

    /** @return array<int,array<string,mixed>> */
    public static function published(string $group = ''): array
    {
        $sql    = sprintf('SELECT * FROM %s WHERE is_published = 1', DB::quoteIdent('faqs'));
        $params = [];
        if ($group !== '') {
            $sql     .= ' AND ' . DB::quoteIdent('group') . ' = ?';
            $params[] = $group;
        }
        return DB::select($sql . ' ORDER BY sort_order ASC, id ASC', $params);
    }

    /** @return array<string,mixed> */
    public static function prepare(array $input): array
    {
        return [
            'question'     => trim((string) ($input['question'] ?? '')),
            'answer'       => trim((string) ($input['answer'] ?? '')),
            'group'        => trim((string) ($input['group'] ?? '')) ?: 'general',
            'sort_order'   => (int) ($input['sort_order'] ?? 0),
            'is_published' => (int) !empty($input['is_published']),
        ];
    }
}
