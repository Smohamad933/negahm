<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Auth;
use App\Core\DB;
use App\Core\Model;
use App\Core\Request;

final class ActivityLog extends Model
{
    protected static string $table = 'activity_logs';
    protected static array $fillable = ['user_id', 'action', 'entity_type', 'entity_id', 'description', 'ip', 'meta', 'created_at'];

    public static function record(string $action, ?string $entityType = null, ?int $entityId = null, string $description = '', array $meta = []): void
    {
        self::create([
            'user_id'     => Auth::id() ?: null,
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'description' => $description !== '' ? mb_substr($description, 0, 500) : null,
            'ip'          => Request::instance()->ip(),
            'meta'        => $meta === [] ? null : (string) json_encode($meta, JSON_UNESCAPED_UNICODE),
            'created_at'  => self::now(),
        ]);
    }

    /** @return array<int,array<string,mixed>> */
    public static function latest(int $limit = 25): array
    {
        return DB::select(sprintf(
            'SELECT a.*, u.name AS user_name
             FROM %1$s a LEFT JOIN %2$s u ON u.id = a.user_id
             ORDER BY a.id DESC LIMIT %3$d',
            DB::quoteIdent('activity_logs'),
            DB::quoteIdent('users'),
            $limit
        ));
    }

    /** @return array{data:array,total:int,page:int,perPage:int,lastPage:int,from:int,to:int} */
    public static function paginateLogs(int $perPage = 30, int $page = 1): array
    {
        $total    = self::count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page     = max(1, min($page, $lastPage));

        $rows = DB::select(sprintf(
            'SELECT a.*, u.name AS user_name
             FROM %1$s a LEFT JOIN %2$s u ON u.id = a.user_id
             ORDER BY a.id DESC LIMIT %3$d OFFSET %4$d',
            DB::quoteIdent('activity_logs'),
            DB::quoteIdent('users'),
            $perPage,
            ($page - 1) * $perPage
        ));

        return [
            'data' => $rows, 'total' => $total, 'page' => $page, 'perPage' => $perPage,
            'lastPage' => $lastPage, 'from' => $total === 0 ? 0 : ($page - 1) * $perPage + 1,
            'to' => ($page - 1) * $perPage + count($rows),
        ];
    }

    /** حذف لاگ‌های قدیمی‌تر از n روز */
    public static function prune(int $days = 90): int
    {
        return DB::run(sprintf(
            'DELETE FROM %s WHERE created_at < ?',
            DB::quoteIdent('activity_logs')
        ), [date('Y-m-d H:i:s', strtotime('-' . $days . ' days'))])->rowCount();
    }
}
