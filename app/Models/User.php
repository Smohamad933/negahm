<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;
use App\Core\Model;

final class User extends Model
{
    protected static string $table = 'users';
    protected static array $fillable = [
        'name', 'username', 'email', 'password', 'role', 'avatar', 'bio',
        'is_active', 'last_login_at', 'updated_at',
    ];

    /** @return array<string,mixed>|null */
    public static function findByIdentifier(string $identifier): ?array
    {
        return DB::first(sprintf(
            'SELECT * FROM %s WHERE %s = ? OR %s = ? LIMIT 1',
            DB::quoteIdent('users'),
            DB::quoteIdent('username'),
            DB::quoteIdent('email')
        ), [$identifier, $identifier]);
    }

    public static function touchLogin(int $id): void
    {
        DB::run(sprintf(
            'UPDATE %s SET %s = ? WHERE %s = ?',
            DB::quoteIdent('users'),
            DB::quoteIdent('last_login_at'),
            DB::quoteIdent('id')
        ), [self::now(), $id]);
    }

    public static function storeRememberToken(int $id, string $selector, string $verifier): void
    {
        DB::run(sprintf(
            'UPDATE %s SET %s = ?, %s = ?, %s = ? WHERE %s = ?',
            DB::quoteIdent('users'),
            DB::quoteIdent('remember_selector'),
            DB::quoteIdent('remember_verifier'),
            DB::quoteIdent('remember_expires'),
            DB::quoteIdent('id')
        ), [$selector, $verifier, date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 30), $id]);
    }

    public static function clearRememberToken(int $id): void
    {
        DB::run(sprintf(
            'UPDATE %s SET %s = NULL, %s = NULL, %s = NULL WHERE %s = ?',
            DB::quoteIdent('users'),
            DB::quoteIdent('remember_selector'),
            DB::quoteIdent('remember_verifier'),
            DB::quoteIdent('remember_expires'),
            DB::quoteIdent('id')
        ), [$id]);
    }

    /** @return array<string,mixed>|null */
    public static function findByRememberSelector(string $selector): ?array
    {
        return DB::first(sprintf(
            'SELECT * FROM %s WHERE %s = ? LIMIT 1',
            DB::quoteIdent('users'),
            DB::quoteIdent('remember_selector')
        ), [$selector]);
    }

    /** @param array<string,mixed> $data */
    public static function register(array $data): int
    {
        $data['password']   = password_hash((string) $data['password'], PASSWORD_DEFAULT);
        $data['created_at'] = self::now();
        return self::create($data);
    }

    public static function updatePassword(int $id, string $plainPassword): void
    {
        DB::update('users', [
            'password'   => password_hash($plainPassword, PASSWORD_DEFAULT),
            'updated_at' => self::now(),
        ], 'id = ?', [$id]);
        self::clearRememberToken($id);
    }
}
