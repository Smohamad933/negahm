<?php

declare(strict_types=1);

namespace App\Core;

/**
 * ابزارهای متنی: اسلاگ فارسی/انگلیسی، برش متن، اعداد فارسی، تاریخ شمسی
 */
final class Str
{
    /** @var array<string,string> */
    private static array $faDigits = ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9'];
    /** @var array<string,string> */
    private static array $arDigits = ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9'];

    /** تبدیل اعداد عربی/فارسی به لاتین */
    public static function toLatinDigits(string $input): string
    {
        return strtr($input, self::$faDigits + self::$arDigits);
    }

    /** تبدیل اعداد لاتین به فارسی */
    public static function toPersianDigits(string|int|float|null $input): string
    {
        return strtr((string) $input, array_flip(self::$faDigits));
    }

    /** یکسان‌سازی حروف عربی به فارسی */
    public static function normalize(string $input): string
    {
        $input = str_replace(["\u{200C}", "\u{200D}", "\u{FEFF}"], '‌', $input);
        return strtr($input, [
            'ي' => 'ی',
            'ك' => 'ک',
            'ة' => 'ه',
            'أ' => 'ا',
            'إ' => 'ا',
            'آ' => 'آ',
            'ؤ' => 'و',
            'ئ' => 'ی',
        ]);
    }

    /** ساخت اسلاگ سازگار با URL از متن فارسی یا لاتین */
    public static function slug(string $value, string $separator = '-'): string
    {
        $value = self::toLatinDigits($value);
        $value = self::normalize($value);
        $value = trim($value);
        // حذف کاراکترهای نامجاز؛ حروف فارسی و لاتین مجازند
        $value = (string) preg_replace('/[^\p{L}\p{N}]+/u', $separator, $value);
        $value = (string) preg_replace('/' . preg_quote($separator, '/') . '+/u', $separator, $value);
        return trim($value, $separator);
    }

    /** تضمین یکتا بودن اسلاگ در جدول */
    public static function uniqueSlug(string $table, string $value, ?int $ignoreId = null): string
    {
        $base = self::slug($value);
        if ($base === '') {
            $base = 'item-' . substr(sha1((string) microtime(true)), 0, 6);
        }
        $slug = $base;
        $i    = 1;
        while (true) {
            $sql    = sprintf('SELECT COUNT(*) FROM %s WHERE slug = ?', DB::quoteIdent($table));
            $params = [$slug];
            if ($ignoreId !== null) {
                $sql     .= ' AND id <> ?';
                $params[] = $ignoreId;
            }
            if ((int) DB::scalar($sql, $params) === 0) {
                return $slug;
            }
            $i++;
            $slug = $base . '-' . $i;
        }
    }

    /** برش متن بدون شکستن کلمه + حذف تگ‌ها */
    public static function excerpt(?string $text, int $limit = 160): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $text)) ?? '');
        if ($text === '') {
            return '';
        }
        if (mb_strlen($text) <= $limit) {
            return $text;
        }
        $cut = mb_substr($text, 0, $limit);
        $pos = mb_strrpos($cut, ' ');
        if ($pos !== false && $pos > $limit * 0.6) {
            $cut = mb_substr($cut, 0, $pos);
        }
        return rtrim($cut, " \t\n\r\0\x0B.,،;؛") . '…';
    }

    /** خوانا کردن اندازه فایل */
    public static function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i     = 0;
        $size  = (float) $bytes;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        return self::toPersianDigits(round($size, $i === 0 ? 0 : 1)) . ' ' . $units[$i];
    }

    public static function limitWords(?string $text, int $words = 30): string
    {
        $text  = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $text)) ?? '');
        $parts = preg_split('/\s+/u', $text) ?: [];
        if (count($parts) <= $words) {
            return $text;
        }
        return implode(' ', array_slice($parts, 0, $words)) . '…';
    }

    public static function random(int $length = 32): string
    {
        return bin2hex(random_bytes((int) ceil($length / 2)));
    }

    /** تبدیل متن ساده به پاراگراف HTML */
    public static function nl2p(?string $text): string
    {
        $text = trim((string) $text);
        if ($text === '') {
            return '';
        }
        $blocks = preg_split('/\R{2,}/u', $text) ?: [];
        $out    = [];
        foreach ($blocks as $block) {
            $block = trim($block);
            if ($block === '') {
                continue;
            }
            $out[] = '<p>' . nl2br(e($block)) . '</p>';
        }
        return implode("\n", $out);
    }
}
