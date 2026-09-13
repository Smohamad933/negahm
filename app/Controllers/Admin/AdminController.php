<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\DB;
use App\Core\Session;
use App\Core\Upload;
use App\Core\View;
use App\Models\Brand;
use App\Models\Media;
use App\Models\Message;
use App\Models\Post;
use App\Models\Work;

/**
 * کلاس پایه کنترلرهای پنل مدیریت
 */
abstract class AdminController extends Controller
{
    public function __construct()
    {
        View::share([
            'adminName'    => (string) setting('site_name', 'نگاه مدیا'),
            'unreadCount'  => Message::unreadCount(),
            'fontCss'      => \App\Models\Font::faceCss(),
            'counters'     => [
                'brands'  => Brand::count('1=1'),
                'works'   => Work::count('1=1'),
                'posts'   => Post::count('1=1'),
                'messages' => Message::count('is_archived = 0'),
            ],
        ]);
    }

    /**
     * بارگذاری یک فایل و ثبت آن در کتابخانه رسانه
     * @param array<string,mixed>|null $file
     * @return array{ok:bool,path?:string,error?:string}
     */
    protected function uploadFile(?array $file, string $folder = 'general', bool $imageOnly = true): array
    {
        if ($file === null) {
            return ['ok' => false, 'error' => 'فایلی ارسال نشد.'];
        }

        $result = Upload::store($file, $folder, $imageOnly);
        if (!$result['ok']) {
            return $result;
        }

        Media::create([
            'name'          => basename((string) $result['path']),
            'original_name' => (string) ($file['name'] ?? ''),
            'path'          => (string) $result['path'],
            'mime'          => (string) ($result['mime'] ?? ''),
            'size'          => (int) ($result['size'] ?? 0),
            'folder'        => $folder,
            'uploaded_by'   => Auth::id() ?: null,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        return $result;
    }

    /** بارگذاری چندتایی؛ فقط مسیرهای موفق برگردانده می‌شود @return array<int,string> */
    protected function uploadMany(?array $files, string $folder = 'works'): array
    {
        if ($files === null || !is_array($files['name'] ?? null)) {
            return [];
        }

        $paths = [];
        foreach (Upload::storeMany($files, $folder, true) as $result) {
            if (empty($result['ok'])) {
                Session::flash('warning', (string) ($result['error'] ?? 'بارگذاری یک فایل ناموفق بود.'));
                continue;
            }
            Media::create([
                'name'          => basename((string) $result['path']),
                'original_name' => '',
                'path'          => (string) $result['path'],
                'mime'          => (string) ($result['mime'] ?? ''),
                'size'          => (int) ($result['size'] ?? 0),
                'folder'        => $folder,
                'uploaded_by'   => Auth::id() ?: null,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
            $paths[] = (string) $result['path'];
        }

        return $paths;
    }

    /** آیا کاربر نقش مدیر دارد؟ */
    protected function requireAdmin(): void
    {
        if (!Auth::isAdmin()) {
            throw new \App\Core\HttpException(403, 'این بخش فقط برای مدیران در دسترس است.');
        }
    }

    protected function counts(): array
    {
        return [
            'brands'   => (int) DB::scalar('SELECT COUNT(*) FROM ' . DB::quoteIdent('brands')),
            'works'    => (int) DB::scalar('SELECT COUNT(*) FROM ' . DB::quoteIdent('works')),
        ];
    }
}
