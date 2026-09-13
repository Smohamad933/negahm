<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\ActivityLog;
use App\Models\Font;

/**
 * مدیریت فونت سایت — بارگذاری فایل فونت و تعیین فونت فعال
 */
final class FontController extends AdminController
{
    public function index(): Response
    {
        return $this->view('admin.fonts.index', [
            'pageTitle' => 'فونت سایت — پنل نگاه مدیا',
            'fonts'     => Font::allFonts(),
            'active'    => Font::active(),
        ]);
    }

    public function store(Request $request): Response
    {
        $data = [
            'name'       => (string) $request->input('name', ''),
            'weight_min' => $request->integer('weight_min', 400),
            'weight_max' => $request->integer('weight_max', 400),
            'is_active'  => $request->boolean('is_active'),
        ];

        $validator = Validator::make($data, [
            'name' => 'required|minlen:2|maxlen:190',
        ], ['name' => 'نام فونت']);

        if ($validator->fails()) {
            Session::flash('danger', $validator->firstError());

            return Response::redirect(url('/admin/fonts'));
        }

        if (!$request->hasFile('file')) {
            Session::flash('danger', 'فایل فونت را انتخاب کنید (woff2، woff، ttf یا otf).');

            return Response::redirect(url('/admin/fonts'));
        }

        $upload = $this->uploadFile($request->file('file'), 'fonts', false);
        if (!$upload['ok']) {
            Session::flash('danger', (string) $upload['error']);

            return Response::redirect(url('/admin/fonts'));
        }

        $prepared = Font::prepare($data, (string) $upload['path']);
        $id       = Font::create($prepared);

        if (!empty($data['is_active'])) {
            Font::activate($id);
        }

        ActivityLog::record('font.create', 'fonts', $id, 'فونت «' . $prepared['name'] . '» بارگذاری شد');
        Session::flash('success', 'فونت «' . $prepared['name'] . '» اضافه شد.');

        return Response::redirect(url('/admin/fonts'));
    }

    public function activate(int $id): Response
    {
        $font = Font::find($id);
        if ($font === null) {
            return $this->notFound('فونت پیدا نشد.');
        }

        Font::activate($id);
        ActivityLog::record('font.activate', 'fonts', $id, 'فونت «' . $font['name'] . '» فعال شد');
        Session::flash('success', 'فونت «' . $font['name'] . '» برای کل سایت فعال شد.');

        return Response::redirect(url('/admin/fonts'));
    }

    /** بازگشت به فونت پیش‌فرض سایت */
    public function reset(): Response
    {
        foreach (Font::allFonts() as $font) {
            Font::updateById((int) $font['id'], ['is_active' => 0]);
        }
        ActivityLog::record('font.reset', 'fonts', null, 'بازگشت به فونت پیش‌فرض');
        Session::flash('success', 'سایت به فونت پیش‌فرض برگشت.');

        return Response::redirect(url('/admin/fonts'));
    }

    public function destroy(int $id): Response
    {
        $font = Font::find($id);
        if ($font === null) {
            return $this->notFound('فونت پیدا نشد.');
        }

        \App\Core\Upload::delete((string) $font['file']);
        Font::deleteById($id);

        ActivityLog::record('font.delete', 'fonts', $id, 'حذف فونت «' . $font['name'] . '»');
        Session::flash('success', 'فونت حذف شد.');

        return Response::redirect(url('/admin/fonts'));
    }
}
