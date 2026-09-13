<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Str;
use App\Core\Validator;
use App\Models\ActivityLog;
use App\Models\WorkCategory;

final class WorkCategoryController extends AdminController
{
    public function index(): Response
    {
        return $this->view('admin.categories.works', [
            'pageTitle'  => 'دسته‌بندی نمونه‌کارها — پنل نگاه مدیا',
            'categories' => WorkCategory::all('sort_order ASC, id ASC'),
        ]);
    }

    public function store(Request $request): Response
    {
        $title = trim((string) $request->input('title', ''));

        $validator = Validator::make(
            ['title' => $title],
            ['title' => 'required|minlen:2|maxlen:190|unique:work_categories,title'],
            ['title' => 'عنوان دسته‌بندی']
        );

        if ($validator->fails()) {
            Session::flash('danger', $validator->firstError());
            return Response::redirect(url('/admin/work-categories'));
        }

        $id = WorkCategory::create([
            'title'        => $title,
            'slug'         => Str::uniqueSlug('work_categories', (string) $request->input('slug', '') ?: $title),
            'label_en'     => trim((string) $request->input('label_en', '')) ?: null,
            'description'  => trim((string) $request->input('description', '')) ?: null,
            'sort_order'   => $request->integer('sort_order', 0),
            'is_published' => $request->boolean('is_published') ? 1 : 0,
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        ActivityLog::record('workCategory.create', 'work_categories', $id, 'دسته‌بندی نمونه‌کار «' . $title . '» ایجاد شد');
        Session::flash('success', 'دسته‌بندی «' . $title . '» اضافه شد.');

        return Response::redirect(url('/admin/work-categories'));
    }

    public function update(Request $request, int $id): Response
    {
        $category = WorkCategory::find($id);
        if ($category === null) {
            return $this->notFound('دسته‌بندی پیدا نشد.');
        }

        $title = trim((string) $request->input('title', ''));
        if ($title === '') {
            Session::flash('danger', 'عنوان دسته‌بندی الزامی است.');
            return Response::redirect(url('/admin/work-categories'));
        }

        WorkCategory::updateById($id, [
            'title'        => $title,
            'slug'         => Str::uniqueSlug('work_categories', (string) $request->input('slug', '') ?: $title, $id),
            'label_en'     => trim((string) $request->input('label_en', '')) ?: null,
            'description'  => trim((string) $request->input('description', '')) ?: null,
            'sort_order'   => $request->integer('sort_order', 0),
            'is_published' => $request->boolean('is_published') ? 1 : 0,
        ]);

        ActivityLog::record('workCategory.update', 'work_categories', $id, 'دسته‌بندی نمونه‌کار «' . $title . '» ویرایش شد');
        Session::flash('success', 'دسته‌بندی بروزرسانی شد.');

        return Response::redirect(url('/admin/work-categories'));
    }

    public function destroy(int $id): Response
    {
        $category = WorkCategory::find($id);
        if ($category === null) {
            return $this->notFound('دسته‌بندی پیدا نشد.');
        }

        WorkCategory::deleteById($id);
        ActivityLog::record('workCategory.delete', 'work_categories', $id, 'دسته‌بندی نمونه‌کار «' . $category['title'] . '» حذف شد');
        Session::flash('success', 'دسته‌بندی حذف شد.');

        return Response::redirect(url('/admin/work-categories'));
    }
}
