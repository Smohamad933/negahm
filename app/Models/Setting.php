<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;
use App\Core\Model;

final class Setting extends Model
{
    protected static string $table = 'settings';
    protected static array $fillable = ['key', 'value', 'type', 'group', 'label', 'updated_at'];

    public static function get(string $key, mixed $default = ''): mixed
    {
        $row = DB::first(sprintf('SELECT * FROM %s WHERE %s = ? LIMIT 1', DB::quoteIdent('settings'), DB::quoteIdent('key')), [$key]);
        if ($row === null) {
            return $default;
        }
        return self::cast($row);
    }

    public static function put(string $key, mixed $value, string $type = 'string', string $group = 'general', ?string $label = null): void
    {
        $stored = is_array($value) ? (string) json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value;
        $exists = DB::first(sprintf('SELECT id FROM %s WHERE %s = ? LIMIT 1', DB::quoteIdent('settings'), DB::quoteIdent('key')), [$key]);

        if ($exists === null) {
            DB::insert('settings', [
                'key'        => $key,
                'value'      => $stored,
                'type'       => $type,
                'group'      => $group,
                'label'      => $label ?? $key,
                'updated_at' => self::now(),
            ]);
            setting_flush();
            return;
        }

        DB::update('settings', [
            'value'      => $stored,
            'type'       => $type,
            'group'      => $group,
            'label'      => $label ?? $key,
            'updated_at' => self::now(),
        ], DB::quoteIdent('key') . ' = ?', [$key]);

        setting_flush();
    }

    /** @param array<string,mixed> $values */
    public static function putMany(array $values): void
    {
        foreach ($values as $key => $value) {
            self::put((string) $key, $value);
        }
    }

    /** @return array<string,mixed> */
    public static function grouped(): array
    {
        $out = [];
        foreach (self::all(DB::quoteIdent('group') . ' ASC, ' . DB::quoteIdent('id') . ' ASC') as $row) {
            $out[$row['group']][$row['key']] = $row;
        }
        return $out;
    }

    /** @param array<string,mixed> $row */
    private static function cast(array $row): mixed
    {
        return match ($row['type']) {
            'int'  => (int) $row['value'],
            'bool' => in_array((string) $row['value'], ['1', 'true', 'on', 'yes'], true),
            'json' => json_decode((string) $row['value'], true) ?? [],
            default => (string) $row['value'],
        };
    }
}
