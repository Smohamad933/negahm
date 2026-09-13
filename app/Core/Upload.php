<?php

declare(strict_types=1);

namespace App\Core;

/**
 * مدیریت بارگذاری فایل: اعتبارسنجی، نام امن، تغییر اندازه تصویر
 */
final class Upload
{
    /**
     * ذخیره یک فایل بارگذاری‌شده
     *
     * @param array<string,mixed> $file یک آیتم از $_FILES
     * @return array{ok:bool,path?:string,error?:string,size?:int,mime?:string}
     */
    public static function store(array $file, string $folder = 'general', bool $imageOnly = true, int $maxWidth = 1920): array
    {
        if (!isset($file['error']) || is_array($file['error'])) {
            return ['ok' => false, 'error' => 'فایل معتبری ارسال نشد.'];
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return ['ok' => false, 'error' => 'حجم فایل بیشتر از حد مجاز است.'];
            case UPLOAD_ERR_NO_FILE:
                return ['ok' => false, 'error' => 'فایلی انتخاب نشده است.'];
            default:
                return ['ok' => false, 'error' => 'بارگذاری فایل با خطا مواجه شد.'];
        }

        $maxSize = (int) Config::get('upload.max_size', 8 * 1024 * 1024);
        if ((int) $file['size'] > $maxSize) {
            return ['ok' => false, 'error' => 'حجم فایل باید کمتر از ' . Str::humanSize($maxSize) . ' باشد.'];
        }

        $original = (string) $file['name'];
        $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $allowed  = $imageOnly
            ? (array) Config::get('upload.image_types', [])
            : (array) Config::get('upload.file_types', []);

        if (!in_array($extension, $allowed, true)) {
            return ['ok' => false, 'error' => 'پسوند «' . $extension . '» مجاز نیست. پسوندهای مجاز: ' . implode('، ', $allowed)];
        }

        $tmp = (string) $file['tmp_name'];
        if (self::isWebSapi() && !is_uploaded_file($tmp)) {
            return ['ok' => false, 'error' => 'بارگذاری فایل نامعتبر است.'];
        }

        $mime = self::mimeOf($tmp, $extension);
        if ($imageOnly && $extension !== 'svg' && !str_starts_with($mime, 'image/')) {
            return ['ok' => false, 'error' => 'محتوای فایل یک تصویر معتبر نیست.'];
        }

        $folder = self::safeFolder($folder);
        $target = self::directory($folder);
        if ($target === null) {
            return ['ok' => false, 'error' => 'ساخت پوشه بارگذاری ممکن نشد. دسترسی public/uploads را بررسی کنید.'];
        }

        $filename = sprintf('%s-%s.%s', date('Ymd-His'), Str::random(8), $extension);
        $fullPath = $target . '/' . $filename;

        $moved = self::isWebSapi() ? @move_uploaded_file($tmp, $fullPath) : @copy($tmp, $fullPath);
        if (!$moved) {
            return ['ok' => false, 'error' => 'ذخیره فایل روی سرور ناموفق بود.'];
        }

        if ($imageOnly && in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            self::resize($fullPath, $maxWidth);
        }

        $relative = '/uploads/' . $folder . '/' . $filename;

        return [
            'ok'   => true,
            'path' => $relative,
            'size' => (int) filesize($fullPath),
            'mime' => $mime,
        ];
    }

    /**
     * بارگذاری چندتایی (آرایه‌ای از فایل‌ها)
     * @return array<int,array<string,mixed>>
     */
    public static function storeMany(array $files, string $folder = 'works', bool $imageOnly = true): array
    {
        $results = [];
        $count   = is_array($files['name'] ?? null) ? count($files['name']) : 0;

        for ($i = 0; $i < $count; $i++) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $single = [
                'name'     => $files['name'][$i],
                'type'     => $files['type'][$i] ?? '',
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i] ?? 0,
            ];
            $results[] = self::store($single, $folder, $imageOnly);
        }

        return $results;
    }

    /** حذف فایل آپلودشده از روی دیسک */
    public static function delete(?string $relativePath): bool
    {
        if (!$relativePath || !str_starts_with($relativePath, '/uploads/')) {
            return false;
        }
        $absolute = realpath((string) Config::get('paths.uploads') . '/' . ltrim(substr($relativePath, strlen('/uploads/')), '/'));
        $root     = realpath((string) Config::get('paths.uploads'));

        if ($absolute === false || $root === false || !str_starts_with($absolute, $root)) {
            return false;
        }
        return @unlink($absolute);
    }

    /** تغییر اندازه تصویر با GD (در صورت موجود بودن) */
    public static function resize(string $path, int $maxWidth = 1920, int $quality = 85): bool
    {
        if (!function_exists('imagecreatefromstring') || !is_file($path)) {
            return false;
        }

        $info = @getimagesize($path);
        if ($info === false) {
            return false;
        }
        [$width, $height] = $info;
        if ($width <= $maxWidth) {
            return false;
        }

        $source = @imagecreatefromstring((string) file_get_contents($path));
        if ($source === false) {
            return false;
        }

        $newHeight = (int) round($height * ($maxWidth / $width));
        $target    = imagecreatetruecolor($maxWidth, $newHeight);
        if ($target === false) {
            imagedestroy($source);
            return false;
        }

        if (in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            imagealphablending($target, false);
            imagesavealpha($target, true);
        }

        imagecopyresampled($target, $source, 0, 0, 0, 0, $maxWidth, $newHeight, $width, $height);

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $saved = match ($extension) {
            'jpg', 'jpeg' => imagejpeg($target, $path, $quality),
            'png'         => imagepng($target, $path, 8),
            'webp'        => function_exists('imagewebp') ? imagewebp($target, $path, $quality) : false,
            'gif'         => imagegif($target, $path),
            default       => false,
        };

        imagedestroy($source);
        imagedestroy($target);

        return (bool) $saved;
    }

    private static function mimeOf(string $path, string $extension): string
    {
        if ($extension === 'svg') {
            return 'image/svg+xml';
        }
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mime = finfo_file($finfo, $path);
                finfo_close($finfo);
                if (is_string($mime) && $mime !== '') {
                    return $mime;
                }
            }
        }
        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'webp'        => 'image/webp',
            'gif'         => 'image/gif',
            'pdf'         => 'application/pdf',
            'mp4'         => 'video/mp4',
            'webm'        => 'video/webm',
            'zip'         => 'application/zip',
            default       => 'application/octet-stream',
        };
    }

    /**
     * آیا برنامه زیر یک وب‌سرور واقعی اجرا می‌شود؟
     * فقط SAPIهای شناخته‌شده وب، آپلود واقعی HTTP دارند؛ بقیه (cli، phpdbg،
     * wasm، embed و اسکریپت‌های خط فرمان) فایل آپلود واقعی ندارند.
     */
    private static function isWebSapi(): bool
    {
        return in_array(PHP_SAPI, [
            'apache2handler', 'apache', 'fpm-fcgi', 'cgi-fcgi', 'cgi', 'litespeed', 'cli-server',
        ], true);
    }

    private static function safeFolder(string $folder): string
    {
        $folder = preg_replace('/[^a-z0-9\-_]/', '', strtolower($folder)) ?? 'general';
        return $folder === '' ? 'general' : $folder;
    }

    private static function directory(string $folder): ?string
    {
        $base = (string) Config::get('paths.uploads');
        $dir  = $base . '/' . $folder;
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return null;
        }
        if (!is_writable($dir)) {
            return null;
        }
        return $dir;
    }
}
