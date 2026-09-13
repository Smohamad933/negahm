<?php
/**
 * پیکربندی اصلی برنامه — نگاه مدیا
 * مقادیر از فایل .env (در ریشه پروژه) خوانده می‌شوند.
 */

declare(strict_types=1);

use App\Core\Env;

return [
    'app' => [
        'name'     => Env::get('APP_NAME', 'نگاه مدیا'),
        'env'      => Env::get('APP_ENV', 'production'),
        'debug'    => Env::bool('APP_DEBUG', false),
        'url'      => rtrim((string) Env::get('APP_URL', ''), '/'),
        'timezone' => Env::get('APP_TIMEZONE', 'Asia/Tehran'),
        'locale'   => Env::get('APP_LOCALE', 'fa'),
        'key'      => Env::get('APP_KEY', 'negahm-default-key-change-me'),
        'base'     => trim((string) Env::get('APP_BASE_PATH', ''), '/'),
    ],

    'db' => [
        'driver'   => Env::get('DB_DRIVER', 'mysql'),
        'host'     => Env::get('DB_HOST', 'localhost'),
        'port'     => (int) Env::get('DB_PORT', 3306),
        'database' => Env::get('DB_DATABASE', 'negahm'),
        'username' => Env::get('DB_USERNAME', 'root'),
        'password' => (string) Env::get('DB_PASSWORD', ''),
        'charset'  => Env::get('DB_CHARSET', 'utf8mb4'),
        // برای اجرای آزمایشی با SQLite کافی است DB_DRIVER=sqlite و DB_DATABASE=مسیر/فایل باشد
        'socket'   => Env::get('DB_SOCKET', ''),
    ],

    'upload' => [
        'max_size'    => (int) Env::get('UPLOAD_MAX_SIZE', 8 * 1024 * 1024),
        'image_types' => explode(',', (string) Env::get('ALLOWED_IMAGE_TYPES', 'jpg,jpeg,png,webp,gif,svg')),
        'file_types'  => explode(',', (string) Env::get('ALLOWED_FILE_TYPES', 'pdf,zip,mp4,webm,woff2,woff,ttf,otf')),
    ],

    'mail' => [
        'from'      => Env::get('MAIL_FROM', 'info@negahm.ir'),
        'from_name' => Env::get('MAIL_FROM_NAME', 'نگاه مدیا'),
        'notify'    => Env::get('MAIL_NOTIFY', ''),
    ],

    'paths' => [
        'root'    => dirname(__DIR__),
        'app'     => dirname(__DIR__) . '/app',
        'views'   => dirname(__DIR__) . '/app/Views',
        'storage' => dirname(__DIR__) . '/storage',
        // فایل‌های بارگذاری‌شده باید داخل پوشه public باشند تا مرورگر
        // بتواند آن‌ها را با آدرس /uploads/... دریافت کند.
        'uploads' => dirname(__DIR__) . '/public/uploads',
        'public'  => dirname(__DIR__) . '/public',
    ],
];
