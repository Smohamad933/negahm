<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;
use App\Core\Model;

final class TeamMember extends Model
{
    protected static string $table = 'team_members';
    protected static array $fillable = ['name', 'role', 'photo', 'bio', 'email', 'socials', 'sort_order', 'is_published', 'created_at'];

    /** @return array<int,array<string,mixed>> */
    public static function published(): array
    {
        return DB::select(sprintf(
            'SELECT * FROM %s WHERE is_published = 1 ORDER BY sort_order ASC, id ASC',
            DB::quoteIdent('team_members')
        ));
    }

    /** @return array<string,mixed> */
    public static function prepare(array $input): array
    {
        $socials = [];
        foreach ((array) ($input['socials'] ?? []) as $key => $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                $socials[$key] = $value;
            }
        }

        return [
            'name'         => trim((string) ($input['name'] ?? '')),
            'role'         => trim((string) ($input['role'] ?? '')) ?: null,
            'bio'          => trim((string) ($input['bio'] ?? '')) ?: null,
            'email'        => trim((string) ($input['email'] ?? '')) ?: null,
            'socials'      => $socials === [] ? null : (string) json_encode($socials, JSON_UNESCAPED_UNICODE),
            'sort_order'   => (int) ($input['sort_order'] ?? 0),
            'is_published' => (int) !empty($input['is_published']),
        ];
    }

    /** @return array<string,string> */
    public static function decodeSocials(?string $json): array
    {
        if (!$json) {
            return [];
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }
}
