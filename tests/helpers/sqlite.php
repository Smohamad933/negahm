<?php

/**
 * ابزار تست — تبدیل DDL میسکی‌ال به SQLite
 *
 * این فایل فقط در محیط تست استفاده می‌شود. سایت در محیط واقعی با
 * MySQL/MariaDB کار می‌کند؛ SQLite تنها برای اجرای خودکار تست‌هاست
 * تا بتوان بدون سرور پایگاه داده، کد را واقعاً اجرا کرد.
 */

declare(strict_types=1);

/**
 * تبدیل اسکیمای MySQL به معادل SQLite.
 */
function mysql_to_sqlite(string $sql): string
{
    // دستورات SET مخصوص MySQL
    $sql = (string) preg_replace('/^\s*SET\s+[^;]+;/mi', '', $sql);

    // کلید اصلی خودافزای
    $sql = (string) preg_replace(
        '/`id`\s+INT\s+UNSIGNED\s+NOT\s+NULL\s+AUTO_INCREMENT/i',
        '`id` INTEGER PRIMARY KEY AUTOINCREMENT',
        $sql
    );
    $sql = (string) preg_replace('/,?\s*\n\s*PRIMARY KEY \(`id`\)/i', '', $sql);

    // انواع داده
    $sql = (string) preg_replace('/\bTINYINT\(1\)/i', 'INTEGER', $sql);
    $sql = (string) preg_replace('/\bSMALLINT\s+UNSIGNED/i', 'INTEGER', $sql);
    $sql = (string) preg_replace('/\bINT\s+UNSIGNED/i', 'INTEGER', $sql);
    $sql = (string) preg_replace('/\bLONGTEXT\b/i', 'TEXT', $sql);
    $sql = (string) preg_replace('/\bVARCHAR\(\d+\)/i', 'TEXT', $sql);
    $sql = (string) preg_replace('/\bENUM\s*\([^)]*\)/i', 'TEXT', $sql);
    $sql = (string) preg_replace('/\bDATETIME\b/i', 'TEXT', $sql);
    $sql = (string) preg_replace('/\s+ON UPDATE CURRENT_TIMESTAMP/i', '', $sql);

    // ایندکس‌ها
    $sql = (string) preg_replace('/\bUNIQUE KEY\s+\S+\s*\(([^)]+)\)/i', 'UNIQUE ($1)', $sql);
    $sql = (string) preg_replace('/,?\s*\n\s*KEY\s+\S+\s*\([^)]*\)/i', '', $sql);

    // گزینه‌های موتور
    $sql = (string) preg_replace('/\)\s*ENGINE=InnoDB[^;]*;/i', ');', $sql);

    return $sql;
}

/**
 * تبدیل توابع زمانی MySQL به SQLite (برای seed.sql)
 */
function mysql_seed_to_sqlite(string $sql): string
{
    $sql = (string) preg_replace('/^\s*SET\s+[^;]+;/mi', '', $sql);
    $sql = str_ireplace('NOW()', "datetime('now')", $sql);
    $sql = str_ireplace('CURDATE()', "date('now')", $sql);
    return $sql;
}

/**
 * ساخت پایگاه داده تست از روی schema.sql و seed.sql واقعی پروژه
 */
function build_test_database(string $root, string $file): PDO
{
    if (is_file($file)) {
        @unlink($file);
    }

    $pdo = new PDO('sqlite:' . $file, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    $schema = mysql_to_sqlite((string) file_get_contents($root . '/database/schema.sql'));
    $pdo->exec($schema);

    $seed = mysql_seed_to_sqlite((string) file_get_contents($root . '/database/seed.sql'));
    $pdo->exec($seed);

    return $pdo;
}
