<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;
use App\Core\Model;
use App\Core\Upload;

final class WorkImage extends Model
{
    protected static string $table = 'work_images';
    protected static array $fillable = ['work_id', 'path', 'alt', 'caption', 'kind', 'sort_order', 'created_at'];

    /** @return array<int,array<string,mixed>> */
    public static function forWork(int $workId): array
    {
        return DB::select(sprintf(
            'SELECT * FROM %s WHERE work_id = ? ORDER BY sort_order ASC, id ASC',
            DB::quoteIdent('work_images')
        ), [$workId]);
    }

    public static function nextSort(int $workId): int
    {
        $max = DB::scalar(sprintf('SELECT MAX(sort_order) FROM %s WHERE work_id = ?', DB::quoteIdent('work_images')), [$workId]);
        return (int) $max + 1;
    }

    public static function remove(int $id): bool
    {
        $row = self::find($id);
        if ($row === null) {
            return false;
        }
        Upload::delete((string) $row['path']);
        return self::deleteById($id) > 0;
    }
}
