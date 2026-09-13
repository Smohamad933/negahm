<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\ActivityLog;
use App\Models\Page;

final class PageController extends AdminController
{
    public function index(): Response
    {
        return $this->view('admin.pages.index', [
            'pageTitle' => 'مدیریت صفحات — پنل نگاه مدیا',
            'pages'     => Page::all('sort_order ASC, id ASC'),
        ]);
    }

    public function create(): Response
    {
        return $this->view('admin.pages.form', [
            'pageTitle' => 'افزودن صفحه — پنل نگاه مدیا',
            'page'      => null,
            'action'    => url('/admin/pages'),
            'heading'   => 'افزودن صفحه جدید',
        ]);
    }

    public function store(Request $request): Response
    {
        $data = $this->collect($request);

        $validator = Validator::make($data, [
            'title' => 'required|minlen:2|maxlen:190',
            'slug'  => 'nullable|slug|maxlen:190',
        ], ['title' => 'عنوان صفحه', 'slug' => 'آدرس یکتا (نامک)']);

        if ($validator->fails()) {
            return $this->withErrors($validator, url('/admin/pages/create'), $request->all());
        }

        $prepared = Page::prepare($data);
        if ($request->hasFile('cover')) {
            $cover = $this->uploadFile($request->file('cover'), 'pages');
            if ($cover['ok']) {
                $prepared['cover'] = $cover['path'];
            }
        }
        $prepared['created_at'] = date('Y-m-d H:i:s');

        $id = Page::create($prepared);
        ActivityLog::record('page.create', 'pages', $id, 'صفحه «' . $prepared['title'] . '» ایجاد شد');
        Session::flash('success', 'صفحه «' . $prepared['title'] . '» ایجاد شد.');

        return Response::redirect(url('/admin/pages/' . $id . '/edit'));
    }

    public function edit(int $id): Response
    {
        $page = Page::find($id);
        if ($page === null) {
            return $this->notFound('صفحه پیدا نشد.');
        }

        return $this->view('admin.pages.form', [
            'pageTitle' => 'ویرایش صفحه — پنل نگاه مدیا',
            'page'      => $page,
            'action'    => url('/admin/pages/' . $id),
            'heading'   => 'ویرایش صفحه: ' . $page['title'],
        ]);
    }

    public function update(Request $request, int $id): Response
    {
        $page = Page::find($id);
        if ($page === null) {
            return $this->notFound('صفحه پیدا نشد.');
        }

        $data = $this->collect($request);

        $validator = Validator::make($data, [
            'title' => 'required|minlen:2|maxlen:190',
            'slug'  => 'nullable|slug|maxlen:190',
        ], ['title' => 'عنوان صفحه', 'slug' => 'آدرس یکتا (نامک)']);

        if ($validator->fails()) {
            return $this->withErrors($validator, url('/admin/pages/' . $id . '/edit'), $request->all());
        }

        $prepared = Page::prepare($data, $id);
        if ($request->hasFile('cover')) {
            $cover = $this->uploadFile($request->file('cover'), 'pages');
            if ($cover['ok']) {
                $prepared['cover'] = $cover['path'];
            }
        } elseif ($request->input('remove_cover')) {
            $prepared['cover'] = null;
        }

        Page::updateById($id, $prepared);
        ActivityLog::record('page.update', 'pages', $id, 'صفحه «' . $prepared['title'] . '» ویرایش شد');
        Session::flash('success', 'تغییرات صفحه ذخیره شد.');

        return Response::redirect(url('/admin/pages/' . $id . '/edit'));
    }

    public function destroy(int $id): Response
    {
        $page = Page::find($id);
        if ($page === null) {
            return $this->notFound('صفحه پیدا نشد.');
        }

        Page::deleteById($id);
        ActivityLog::record('page.delete', 'pages', $id, 'صفحه «' . $page['title'] . '» حذف شد');
        Session::flash('success', 'صفحه حذف شد.');

        return Response::redirect(url('/admin/pages'));
    }

    /** @return array<string,mixed> */
    private function collect(Request $request): array
    {
        return [
            'title'          => (string) $request->input('title', ''),
            'slug'           => (string) $request->input('slug', ''),
            'subtitle'       => (string) $request->input('subtitle', ''),
            'body'           => (string) $request->input('body', ''),
            'template'       => (string) $request->input('template', 'default'),
            'show_in_menu'   => $request->boolean('show_in_menu'),
            'show_in_footer' => $request->boolean('show_in_footer'),
            'is_published'   => $request->boolean('is_published'),
            'sort_order'     => $request->integer('sort_order', 0),
            'seo_title'      => (string) $request->input('seo_title', ''),
            'seo_description' => (string) $request->input('seo_description', ''),
        ];
    }

    private function withErrors(Validator $validator, string $to, array $old): Response
    {
        Session::flash('_errors', $validator->errors());
        Session::flash('_old', $old);
        Session::flash('danger', $validator->firstError());
        return Response::redirect($to);
    }
}
