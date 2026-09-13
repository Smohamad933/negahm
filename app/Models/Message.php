<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;
use App\Core\Model;

final class Message extends Model
{
    protected static string $table = 'messages';
    protected static array $fillable = [
        'name', 'email', 'phone', 'company', 'subject', 'service_id', 'budget', 'body',
        'source', 'ip', 'user_agent', 'is_read', 'is_archived', 'reply_note', 'created_at',
    ];

    private const SELECT_LIST = 'm.*, s.title AS service_title';

    private static function fromClause(): string
    {
        return DB::quoteIdent('messages') . ' m LEFT JOIN ' . DB::quoteIdent('services') . ' s ON s.id = m.service_id';
    }

    /** @return array{data:array,total:int,page:int,perPage:int,lastPage:int,from:int,to:int} */
    public static function paginateFiltered(array $filters = [], int $perPage = 15, int $page = 1): array
    {
        $where  = ['1=1'];
        $params = [];

        if (isset($filters['status']) && $filters['status'] !== '') {
            if ($filters['status'] === 'unread') {
                $where[] = 'm.is_read = 0';
            } elseif ($filters['status'] === 'read') {
                $where[] = 'm.is_read = 1';
            } elseif ($filters['status'] === 'archived') {
                $where[] = 'm.is_archived = 1';
            }
        } else {
            $where[] = 'm.is_archived = 0';
        }

        if (!empty($filters['q'])) {
            $where[]  = '(m.name LIKE ? OR m.email LIKE ? OR m.phone LIKE ? OR m.subject LIKE ? OR m.body LIKE ?)';
            $like     = '%' . $filters['q'] . '%';
            array_push($params, $like, $like, $like, $like, $like);
        }

        $whereSql = implode(' AND ', $where);
        $total    = (int) DB::scalar('SELECT COUNT(*) FROM ' . self::fromClause() . ' WHERE ' . $whereSql, $params);
        $lastPage = max(1, (int) ceil($total / max(1, $perPage)));
        $page     = max(1, min($page, $lastPage));

        $rows = DB::select(sprintf(
            'SELECT %s FROM %s WHERE %s ORDER BY m.created_at DESC, m.id DESC LIMIT %d OFFSET %d',
            self::SELECT_LIST,
            self::fromClause(),
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

    /** @return array<string,mixed>|null */
    public static function withService(int $id): ?array
    {
        return DB::first(sprintf('SELECT %s FROM %s WHERE m.id = ? LIMIT 1', self::SELECT_LIST, self::fromClause()), [$id]);
    }

    public static function unreadCount(): int
    {
        return (int) DB::scalar(sprintf(
            'SELECT COUNT(*) FROM %s WHERE is_read = 0 AND is_archived = 0',
            DB::quoteIdent('messages')
        ));
    }

    public static function markRead(int $id, bool $read = true): void
    {
        DB::run(sprintf('UPDATE %s SET is_read = ? WHERE id = ?', DB::quoteIdent('messages')), [$read ? 1 : 0, $id]);
    }

    public static function archive(int $id, bool $archived = true): void
    {
        DB::run(sprintf('UPDATE %s SET is_archived = ? WHERE id = ?', DB::quoteIdent('messages')), [$archived ? 1 : 0, $id]);
    }

    /** تعداد پیام‌ها در n روز اخیر (برای نمودار داشبورد) @return array<int,int> */
    public static function dailyCounts(int $days = 14): array
    {
        $rows = DB::select(sprintf(
            'SELECT DATE(created_at) AS d, COUNT(*) AS c FROM %s WHERE created_at >= ? GROUP BY DATE(created_at)',
            DB::quoteIdent('messages')
        ), [date('Y-m-d 00:00:00', strtotime('-' . ($days - 1) . ' days'))]);

        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row['d']] = (int) $row['c'];
        }

        $out = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date          = date('Y-m-d', strtotime('-' . $i . ' days'));
            $out[$date] = $map[$date] ?? 0;
        }
        return $out;
    }
}
