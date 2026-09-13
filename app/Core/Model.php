<?php

declare(strict_types=1);

namespace App\Core;

/**
 * مدل پایه با کوئری‌ساز کوچک و امن (Prepared Statement)
 */
abstract class Model
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';

    /** ستون‌هایی که مجاز به ذخیره‌شدن هستند */
    protected static array $fillable = [];

    public static function table(): string
    {
        return static::$table;
    }

    public static function all(string $orderBy = 'id DESC'): array
    {
        return DB::select(sprintf(
            'SELECT * FROM %s ORDER BY %s',
            DB::quoteIdent(static::$table),
            static::sanitizeOrder($orderBy)
        ));
    }

    /** @return array<string,mixed>|null */
    public static function find(int $id): ?array
    {
        return DB::first(sprintf(
            'SELECT * FROM %s WHERE %s = ? LIMIT 1',
            DB::quoteIdent(static::$table),
            DB::quoteIdent(static::$primaryKey)
        ), [$id]);
    }

    /** @return array<string,mixed>|null */
    public static function findBySlug(string $slug, bool $publishedOnly = false): ?array
    {
        $sql    = sprintf('SELECT * FROM %s WHERE slug = ?', DB::quoteIdent(static::$table));
        $params = [$slug];
        if ($publishedOnly) {
            $sql .= ' AND is_published = 1';
        }
        return DB::first($sql . ' LIMIT 1', $params);
    }

    public static function count(string $where = '1=1', array $params = []): int
    {
        return (int) DB::scalar(sprintf(
            'SELECT COUNT(*) FROM %s WHERE %s',
            DB::quoteIdent(static::$table),
            $where
        ), $params);
    }

    public static function exists(int $id): bool
    {
        return self::find($id) !== null;
    }

    /** @param array<string,mixed> $data */
    public static function create(array $data): int
    {
        return DB::insert(static::$table, static::filterFillable($data));
    }

    /** @param array<string,mixed> $data */
    public static function updateById(int $id, array $data): int
    {
        $filtered = static::filterFillable($data);
        if ($filtered === []) {
            return 0;
        }
        return DB::update(static::$table, $filtered, DB::quoteIdent(static::$primaryKey) . ' = ?', [$id]);
    }

    public static function deleteById(int $id): int
    {
        return DB::delete(static::$table, DB::quoteIdent(static::$primaryKey) . ' = ?', [$id]);
    }

    public static function toggle(int $id, string $column): bool
    {
        if (!preg_match('/^[a-z0-9_]+$/', $column)) {
            return false;
        }
        $current = DB::scalar(sprintf(
            'SELECT %s FROM %s WHERE %s = ? LIMIT 1',
            DB::quoteIdent($column),
            DB::quoteIdent(static::$table),
            DB::quoteIdent(static::$primaryKey)
        ), [$id]);
        if ($current === null) {
            return false;
        }
        DB::run(sprintf(
            'UPDATE %s SET %s = ? WHERE %s = ?',
            DB::quoteIdent(static::$table),
            DB::quoteIdent($column),
            DB::quoteIdent(static::$primaryKey)
        ), [(int) $current === 1 ? 0 : 1, $id]);

        return true;
    }

    /**
     * صفحه‌بندی ساده
     * @return array{data:array<int,array<string,mixed>>,total:int,page:int,perPage:int,lastPage:int,from:int,to:int}
     */
    public static function paginate(string $select = '*', string $where = '1=1', array $params = [], int $perPage = 12, int $page = 1, string $orderBy = 'id DESC'): array
    {
        $table = DB::quoteIdent(static::$table);
        $total = (int) DB::scalar(sprintf('SELECT COUNT(*) FROM %s WHERE %s', $table, $where), $params);

        $lastPage = max(1, (int) ceil($total / max(1, $perPage)));
        $page     = max(1, min($page, $lastPage));
        $offset   = ($page - 1) * $perPage;

        $rows = DB::select(sprintf(
            'SELECT %s FROM %s WHERE %s ORDER BY %s LIMIT %d OFFSET %d',
            $select,
            $table,
            $where,
            static::sanitizeOrder($orderBy),
            $perPage,
            $offset
        ), $params);

        return [
            'data'     => $rows,
            'total'    => $total,
            'page'     => $page,
            'perPage'  => $perPage,
            'lastPage' => $lastPage,
            'from'     => $total === 0 ? 0 : $offset + 1,
            'to'       => $offset + count($rows),
        ];
    }

    /** @param array<string,mixed> $data @return array<string,mixed> */
    protected static function filterFillable(array $data): array
    {
        if (static::$fillable === []) {
            return $data;
        }
        return array_intersect_key($data, array_flip(static::$fillable));
    }

    protected static function sanitizeOrder(string $orderBy): string
    {
        // فقط ستون‌ها و جهت‌های مجاز
        $parts = preg_split('/\s*,\s*/', trim($orderBy)) ?: [];
        $clean = [];
        foreach ($parts as $part) {
            $tokens = preg_split('/\s+/', trim($part)) ?: [];
            $column = $tokens[0] ?? '';
            if ($column === '' || preg_match('/[^a-zA-Z0-9_.]/', $column)) {
                continue;
            }
            $direction = strtoupper($tokens[1] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
            $clean[]   = str_contains($column, '.')
                ? implode('.', array_map([DB::class, 'quoteIdent'], explode('.', $column))) . ' ' . $direction
                : DB::quoteIdent($column) . ' ' . $direction;
        }
        return $clean === [] ? DB::quoteIdent(static::$primaryKey) . ' DESC' : implode(', ', $clean);
    }

    public static function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
