<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;
use App\Core\Model;
use App\Core\Str;

/**
 * فونت‌های بارگذاری‌شده از پنل
 *
 * در هر لحظه فقط یک فونت فعال است و همان فونت کل سایت می‌شود؛ اگر هیچ فونتی
 * فعال نباشد، سایت از فونت پیش‌فرض (وزیرمتن) استفاده می‌کند.
 */
final class Font extends Model
{
    protected static string $table = 'fonts';
    protected static array $fillable = [
        'name', 'slug', 'file', 'format', 'weight_min', 'weight_max', 'is_active', 'created_at',
    ];

    /** نگاشت پسوند فایل به مقدار format در @font-face */
    public const FORMATS = [
        'woff2' => 'woff2',
        'woff'  => 'woff',
        'ttf'   => 'truetype',
        'otf'   => 'opentype',
    ];

    /** @return array<int,array<string,mixed>> */
    public static function allFonts(): array
    {
        return DB::select(sprintf(
            'SELECT * FROM %s ORDER BY is_active DESC, id DESC',
            DB::quoteIdent('fonts')
        ));
    }

    /** فونت فعال فعلی سایت */
    public static function active(): ?array
    {
        return DB::first(sprintf(
            'SELECT * FROM %s WHERE is_active = 1 ORDER BY id ASC LIMIT 1',
            DB::quoteIdent('fonts')
        ));
    }

    /** فعال‌کردن یک فونت و غیرفعال‌کردن بقیه */
    public static function activate(int $id): void
    {
        DB::update('fonts', ['is_active' => 0], 'is_active = 1');
        DB::update('fonts', ['is_active' => 1], 'id = ?', [$id]);
    }

    /** @return array<string,mixed> */
    public static function prepare(array $input, string $file, ?int $ignoreId = null): array
    {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $name      = trim((string) ($input['name'] ?? ''));

        return [
            'name'       => $name !== '' ? $name : pathinfo($file, PATHINFO_FILENAME),
            'slug'       => Str::uniqueSlug('fonts', $name !== '' ? $name : pathinfo($file, PATHINFO_FILENAME), $ignoreId),
            'file'       => $file,
            'format'     => self::FORMATS[$extension] ?? 'woff2',
            'weight_min' => max(1, (int) ($input['weight_min'] ?? 400)),
            'weight_max' => max(1, (int) ($input['weight_max'] ?? 400)),
            'is_active'  => (int) !empty($input['is_active']),
            'created_at' => self::now(),
        ];
    }

    /**
     * ساخت CSS فونت فعال برای درج در <head> سایت.
     * اگر فونتی فعال نباشد، رشته‌ی خالی برمی‌گرداند.
     */
    public static function faceCss(): string
    {
        $font = self::active();
        if ($font === null) {
            return '';
        }

        // نام خانوادگی فونت در CSS عمداً ثابت است: هیچ ورودی کاربری وارد CSS
        // نمی‌شود، پس راهی برای تزریق استایل وجود ندارد.
        $family = 'negahm-font';
        $min    = (int) $font['weight_min'];
        $max    = (int) $font['weight_max'];
        $range  = $min === $max ? (string) $min : $min . ' ' . $max;

        return '@font-face{'
            . "font-family:'{$family}';"
            . 'src:url(' . upload_url((string) $font['file']) . ") format('" . $font['format'] . "');"
            . "font-weight:{$range};font-style:normal;font-display:swap;}"
            . ":root{--font:'{$family}',ui-sans-serif,system-ui,-apple-system,'Segoe UI',sans-serif;}";
    }

    /** نام فونت فعال برای نمایش در پنل */
    public static function activeLabel(): string
    {
        $font = self::active();

        return $font === null ? 'پیش‌فرض (وزیرمتن)' : (string) $font['name'];
    }
}
