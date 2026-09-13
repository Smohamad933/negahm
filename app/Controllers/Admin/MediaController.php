<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\ActivityLog;
use App\Models\Media;

final class MediaController extends AdminController
{
    public function index(Request $request): Response
    {
        return $this->view('admin.media.index', [
            'pageTitle' => 'کتابخانه رسانه — پنل نگاه مدیا',
            'pager'     => Media::paginateFiltered(
                trim((string) $request->query('q', '')),
                trim((string) $request->query('folder', '')),
                24,
                max(1, $request->integer('page', 1))
            ),
            'folders'   => Media::folders(),
            'q'         => trim((string) $request->query('q', '')),
            'folder'    => trim((string) $request->query('folder', '')),
            'totalSize' => Media::totalSize(),
        ]);
    }

    public function store(Request $request): Response
    {
        $folder = preg_replace('/[^a-z0-9\-_]/', '', strtolower((string) $request->input('folder', 'general'))) ?: 'general';
        $isFile = $request->boolean('allow_file');

        $paths = $request->hasFile('files')
            ? $this->uploadMany($request->file('files'), $folder)
            : [];

        if ($paths === []) {
            if ($request->hasFile('file')) {
                $single = $this->uploadFile($request->file('file'), $folder, !$isFile);
                if ($single['ok']) {
                    $paths[] = (string) $single['path'];
                } else {
                    Session::flash('danger', (string) ($single['error'] ?? 'بارگذاری ناموفق بود.'));
                    if ($request->wantsJson()) {
                        return $this->fail((string) ($single['error'] ?? 'بارگذاری ناموفق بود.'));
                    }
                    return Response::redirect(url('/admin/media'));
                }
            } else {
                Session::flash('danger', 'فایلی برای بارگذاری انتخاب نشد.');
                if ($request->wantsJson()) {
                    return $this->fail('فایلی برای بارگذاری انتخاب نشد.');
                }
                return Response::redirect(url('/admin/media'));
            }
        }

        ActivityLog::record('media.upload', 'media', null, count($paths) . ' فایل بارگذاری شد');
        Session::flash('success', count($paths) . ' فایل با موفقیت بارگذاری شد.');

        if ($request->wantsJson()) {
            return $this->ok(count($paths) . ' فایل بارگذاری شد', ['paths' => $paths]);
        }
        return Response::redirect(url('/admin/media'));
    }

    public function destroy(Request $request, int $id): Response
    {
        $item = Media::find($id);
        if ($item === null) {
            return $this->fail('فایل پیدا نشد.', 404);
        }

        Media::remove($id);
        ActivityLog::record('media.delete', 'media', $id, 'حذف فایل «' . $item['name'] . '»');

        if ($request->wantsJson()) {
            return $this->ok('فایل حذف شد');
        }
        Session::flash('success', 'فایل حذف شد.');
        return Response::redirect(url('/admin/media'));
    }
}
