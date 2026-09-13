<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;
use App\Core\Model;
use App\Core\Request;

final class Subscriber extends Model
{
    protected static string $table = 'subscribers';
    protected static array $fillable = ['email', 'ip', 'is_active', 'created_at'];

    /** @return array{ok:bool,message:string} */
    public static function subscribe(string $email): array
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'message' => 'ایمیل وارد شده معتبر نیست.'];
        }

        $exists = DB::first(sprintf('SELECT id FROM %s WHERE email = ? LIMIT 1', DB::quoteIdent('subscribers')), [$email]);
        if ($exists !== null) {
            return ['ok' => false, 'message' => 'این ایمیل قبلاً ثبت شده است.'];
        }

        self::create([
            'email'      => $email,
            'ip'         => Request::instance()->ip(),
            'is_active'  => 1,
            'created_at' => self::now(),
        ]);

        return ['ok' => true, 'message' => 'عضویت شما ثبت شد. ممنون که همراه ما هستید.'];
    }

    /** @return array<int,array<string,mixed>> */
    public static function latest(int $limit = 20): array
    {
        return DB::select(sprintf('SELECT * FROM %s ORDER BY id DESC LIMIT %d', DB::quoteIdent('subscribers'), $limit));
    }
}
