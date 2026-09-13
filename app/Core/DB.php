<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

/**
 * لایه اتصال به پایگاه داده (PDO)
 * از MySQL/MariaDB پشتیبانی می‌کند و برای اجرای آزمایشی SQLite نیز قابل استفاده است.
 */
final class DB
{
    private static ?PDO $pdo = null;
    /** @var array<int,array{sql:string,time:float,params:array}> */
    private static array $log = [];

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $driver   = (string) Config::get('db.driver', 'mysql');
        $charset  = (string) Config::get('db.charset', 'utf8mb4');
        $database = (string) Config::get('db.database', '');

        if ($driver === 'sqlite') {
            $dsn = 'sqlite:' . $database;
            $pdo = new PDO($dsn, null, null, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            $pdo->exec('PRAGMA foreign_keys = ON');
            self::$pdo = $pdo;
            return $pdo;
        }

        $socket = (string) Config::get('db.socket', '');
        $host   = (string) Config::get('db.host', 'localhost');
        $port   = (int) Config::get('db.port', 3306);
        $dsn    = $socket !== ''
            ? sprintf('mysql:unix_socket=%s;dbname=%s;charset=%s', $socket, $database, $charset)
            : sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $database, $charset);

        try {
            $pdo = new PDO($dsn, (string) Config::get('db.username', 'root'), (string) Config::get('db.password', ''), [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                self::initCommandAttribute() => "SET NAMES {$charset}",
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException('اتصال به پایگاه داده برقرار نشد: ' . $e->getMessage(), 0, $e);
        }

        self::$pdo = $pdo;
        return $pdo;
    }

    /**
     * نام ثابت «دستور اولیهٔ اتصال» برای درایور MySQL.
     *
     * PHP 8.5 ثابت PDO::MYSQL_ATTR_INIT_COMMAND را منسوخ کرده و
     * Pdo\Mysql::ATTR_INIT_COMMAND را جایگزینش کرده است (کلاس Pdo\Mysql از
     * PHP 8.4 وجود دارد). ثابت فقط در شاخهٔ انتخاب‌شدهٔ شرط خوانده می‌شود،
     * پس روی هیچ نسخه‌ای اخطار انسوخ صادر نمی‌شود.
     *
     * @return int|string
     */
    public static function initCommandAttribute(): int|string
    {
        return class_exists('Pdo\Mysql')
            ? \Pdo\Mysql::ATTR_INIT_COMMAND
            : \PDO::MYSQL_ATTR_INIT_COMMAND;
    }

    public static function driver(): string
    {
        return (string) Config::get('db.driver', 'mysql');
    }

    public static function isMysql(): bool
    {
        return self::driver() === 'mysql';
    }

    /**
     * @param array<int|string,mixed> $params
     */
    public static function run(string $sql, array $params = []): PDOStatement
    {
        $start = microtime(true);
        $stmt  = self::pdo()->prepare($sql);
        foreach ($params as $key => $value) {
            if (is_int($key)) {
                $stmt->bindValue($key + 1, $value);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        $stmt->execute();
        self::$log[] = ['sql' => $sql, 'time' => microtime(true) - $start, 'params' => $params];
        return $stmt;
    }

    /**
     * @param array<int|string,mixed> $params
     * @return array<int,array<string,mixed>>
     */
    public static function select(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    /**
     * @param array<int|string,mixed> $params
     * @return array<string,mixed>|null
     */
    public static function first(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /**
     * @param array<int|string,mixed> $params
     */
    public static function scalar(string $sql, array $params = []): mixed
    {
        $value = self::run($sql, $params)->fetchColumn();
        return $value === false ? null : $value;
    }

    /**
     * @param array<int|string,mixed> $params
     */
    public static function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $sql     = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            self::quoteIdent($table),
            implode(', ', array_map([self::class, 'quoteIdent'], $columns)),
            implode(', ', array_fill(0, count($columns), '?'))
        );
        self::run($sql, array_values($data));
        return (int) self::pdo()->lastInsertId();
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $sets = [];
        foreach (array_keys($data) as $column) {
            $sets[] = self::quoteIdent($column) . ' = ?';
        }
        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            self::quoteIdent($table),
            implode(', ', $sets),
            $where
        );
        return self::run($sql, array_merge(array_values($data), $whereParams))->rowCount();
    }

    public static function delete(string $table, string $where, array $params = []): int
    {
        return self::run(sprintf('DELETE FROM %s WHERE %s', self::quoteIdent($table), $where), $params)->rowCount();
    }

    public static function quoteIdent(string $identifier): string
    {
        if (self::isMysql()) {
            return '`' . str_replace('`', '``', $identifier) . '`';
        }
        return '"' . str_replace('"', '""', $identifier) . '"';
    }

    public static function transaction(callable $callback): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function log(): array
    {
        return self::$log;
    }

    public static function disconnect(): void
    {
        self::$pdo = null;
    }
}
