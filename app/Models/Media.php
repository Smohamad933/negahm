<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;
use App\Core\Model;
use App\Core\Upload;

final class Media extends Model
{
    protected static string $table = 'media';
    protected static array $fillable = ['name', 'original_name', 'path', 'mime', 'size', 'folder', 'alt', 'uploaded_by', 'created_at'];

    /** @return array{data:array,total:int,page:int,perPage:int,lastPage:int,from:int,to:int} */
    public static function paginateFiltered(string $search = '', string $folder = '', int $perPage = 24, int $page = 1): array
    {
        $where  = ['1=1'];
        $params = [];

        if ($search !== '') {
            $where[]  = '(name LIKE ? OR original_name LIKE ?)';
            $like     = '%' . $search . '%';
            array_push($params, $like, $like);
        }
        if ($folder !== '') {
            $where[]  = 'folder = ?';
            $params[] = $folder;
        }

        $whereSql = implode(' AND ', $where);
        $total    = (int) DB::scalar(sprintf('SELECT COUNT(*) FROM %s WHERE %s', DB::quoteIdent('media'), $whereSql), $params);
        $lastPage = max(1, (int) ceil($total / max(1, $perPage)));
        $page     = max(1, min($page, $lastPage));

        $rows = DB::select(sprintf(
            'SELECT * FROM %s WHERE %s ORDER BY id DESC LIMIT %d OFFSET %d',
            DB::quoteIdent('media'),
            $whereSql,
            $perPage,
            ($page - 1) * $perPage
        ), $params);

        return [
            'data' => $rows, 'total' => $total, 'page' => $page, 'perPage' => $perPage,
            'lastPage' => $lastPage, 'from' => $total === 0 ? 0 : ($page - 1) * $perPage + 1,
            'to' => ($page - 1) * $perPage + count($rows),
        ];
    }

    /** @return array<int,string> */
    public static function folders(): array
    {
        $rows = DB::select(sprintf(
            'SELECT DISTINCT folder FROM %s WHERE folder IS NOT NULL ORDER BY folder ASC',
            DB::quoteIdent('media')
        ));
        return array_map(static fn ($r) => (string) $r['folder'], $rows);
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

    public static function totalSize(): int
    {
        return (int) DB::scalar(sprintf('SELECT COALESCE(SUM(size),0) FROM %s', DB::quoteIdent('media')));
    }
}
