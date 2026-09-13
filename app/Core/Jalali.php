<?php

declare(strict_types=1);

namespace App\Core;

/**
 * تبدیل تاریخ میلادی به شمسی و برعکس + نمایش «زمان نسبی»
 */
final class Jalali
{
    /** @param array<int,int> */
    private static array $gDaysInMonth = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    /** @param array<int,int> */
    private static array $jDaysInMonth = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];

    /** @return array{0:int,1:int,2:int} [سال، ماه، روز] */
    public static function toJalali(int $gy, int $gm, int $gd): array
    {
        $gDaysInMonth = self::$gDaysInMonth;
        $jDaysInMonth = self::$jDaysInMonth;

        $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
        $days = 355666 + (365 * $gy) + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100)
            + intdiv($gy2 + 399, 400) + $gd;
        for ($i = 0; $i < $gm - 1; $i++) {
            $days += $gDaysInMonth[$i];
        }
        // روز کبیسه میلادی در خودِ $gy2 لحاظ شده است؛ افزودن دوباره آن
        // باعث می‌شد همه تاریخ‌های بعد از اسفند در سال کبیسه یک روز جلو بیفتند.

        $jy = -1595 + (33 * intdiv($days, 12053));
        $days %= 12053;

        $jy += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $jy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        $i = 0;
        while ($i < 11 && $days >= $jDaysInMonth[$i]) {
            $days -= $jDaysInMonth[$i];
            $i++;
        }

        return [$jy, $i + 1, $days + 1];
    }

    /** @return array{0:int,1:int,2:int} [سال، ماه، روز] */
    public static function toGregorian(int $jy, int $jm, int $jd): array
    {
        $jDaysInMonth = self::$jDaysInMonth;

        $jy += 1595;
        $days = -355668 + (365 * $jy) + (intdiv($jy, 33) * 8) + intdiv((($jy % 33) + 3), 4) + $jd;
        for ($i = 0; $i < $jm - 1; $i++) {
            $days += $jDaysInMonth[$i];
        }

        $gy = 400 * intdiv($days, 146097);
        $days %= 146097;
        if ($days > 36524) {
            $gy += 100 * intdiv(--$days, 36524);
            $days %= 36524;
            if ($days >= 365) {
                $days++;
            }
        }
        $gy += 4 * intdiv($days, 1461);
        $days %= 1461;
        if ($days > 365) {
            $gy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        $gd = $days + 1;
        $gDaysInMonth = self::$gDaysInMonth;
        if ((($gy % 4 === 0 && $gy % 100 !== 0) || ($gy % 400 === 0))) {
            $gDaysInMonth[1] = 29;
        }
        $i = 0;
        while ($i < 12 && $gd > $gDaysInMonth[$i]) {
            $gd -= $gDaysInMonth[$i];
            $i++;
        }

        return [$gy, $i + 1, $gd];
    }

    /** @var array<int,string> */
    public static array $monthNames = [
        'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
        'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند',
    ];

    /** @var array<int,string> */
    public static array $weekDays = ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'];

    /**
     * قالب‌بندی تاریخ شمسی
     * پشتیبانی از: Y y m d j F l H i s
     */
    public static function format(?string $datetime, string $pattern = 'j F Y', bool $withTime = false): string
    {
        if ($datetime === null || $datetime === '' || str_starts_with($datetime, '0000')) {
            return '';
        }
        $ts = strtotime($datetime);
        if ($ts === false) {
            return '';
        }
        [$jy, $jm, $jd] = self::toJalali((int) date('Y', $ts), (int) date('n', $ts), (int) date('j', $ts));

        $replacements = [
            'Y' => (string) $jy,
            'y' => substr((string) $jy, -2),
            'm' => str_pad((string) $jm, 2, '0', STR_PAD_LEFT),
            'd' => str_pad((string) $jd, 2, '0', STR_PAD_LEFT),
            'j' => (string) $jd,
            'F' => self::$monthNames[$jm - 1] ?? '',
            'l' => self::weekDayOf($ts),
            'H' => date('H', $ts),
            'i' => date('i', $ts),
            's' => date('s', $ts),
        ];

        $out = strtr($pattern, $replacements);
        $out = Str::toPersianDigits($out);
        if ($withTime) {
            $out .= ' — ' . Str::toPersianDigits(date('H:i', $ts));
        }
        return $out;
    }

    public static function weekDayOf(int $ts): string
    {
        // date('N') : 1=دوشنبه … 7=یکشنبه در PHP؛ نگاشت به هفته ایرانی
        $map = [6 => 0, 7 => 1, 1 => 2, 2 => 3, 3 => 4, 4 => 5, 5 => 6];
        return self::$weekDays[$map[(int) date('N', $ts)]] ?? '';
    }

    /** «۳ روز پیش» */
    public static function ago(?string $datetime): string
    {
        if (!$datetime) {
            return '';
        }
        $ts = strtotime($datetime);
        if ($ts === false) {
            return '';
        }
        $diff = time() - $ts;
        if ($diff < 60) {
            return 'همین حالا';
        }
        if ($diff < 3600) {
            return Str::toPersianDigits(intdiv($diff, 60)) . ' دقیقه پیش';
        }
        if ($diff < 86400) {
            return Str::toPersianDigits(intdiv($diff, 3600)) . ' ساعت پیش';
        }
        if ($diff < 86400 * 7) {
            return Str::toPersianDigits(intdiv($diff, 86400)) . ' روز پیش';
        }
        return self::format($datetime, 'j F Y');
    }
}
