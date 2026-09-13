<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Str;
use App\Core\Validator;
use App\Models\ActivityLog;
use App\Models\BrandCategory;

final class BrandCategoryController extends AdminController
{
    public function index(): Response
    {
        return $this->view('admin.categories.brands', [
            'pageTitle'  => 'دسته‌بندی برندها — پنل نگاه مدیا',
            'categories' => BrandCategory::all('sort_order ASC, id ASC'),
        ]);
    }

    public function store(Request $request): Response
    {
        $title = trim((string) $request->input('title', ''));

        $validator = Validator::make(
            ['title' => $title],
            ['title' => 'required|minlen:2|maxlen:190|unique:brand_categories,title'],
            ['title' => 'عنوان دسته‌بندی']
        );

        if ($validator->fails()) {
            Session::flash('danger', $validator->firstError());
            return Response::redirect(url('/admin/brand-categories'));
        }

        $id = BrandCategory::create([
            'title'        => $title,
            'slug'         => Str::uniqueSlug('brand_categories', (string) $request->input('slug', '') ?: $title),
            'description'  => trim((string) $request->input('description', '')) ?: null,
            'sort_order'   => $request->integer('sort_order', 0),
            'is_published' => $request->boolean('is_published') ? 1 : 0,
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        ActivityLog::record('brandCategory.create', 'brand_categories', $id, 'دسته‌بندی برند «' . $title . '» ایجاد شد');
        Session::flash('success', 'دسته‌بندی «' . $title . '» اضافه شد.');

        return Response::redirect(url('/admin/brand-categories'));
    }

    public function update(Request $request, int $id): Response
    {
        $category = BrandCategory::find($id);
        if ($category === null) {
            return $this->notFound('دسته‌بندی پیدا نشد.');
        }

        $title = trim((string) $request->input('title', ''));
        if ($title === '') {
            Session::flash('danger', 'عنوان دسته‌بندی الزامی است.');
            return Response::redirect(url('/admin/brand-categories'));
        }

        BrandCategory::updateById($id, [
            'title'        => $title,
            'slug'         => Str::uniqueSlug('brand_categories', (string) $request->input('slug', '') ?: $title, $id),
            'description'  => trim((string) $request->input('description', '')) ?: null,
            'sort_order'   => $request->integer('sort_order', 0),
            'is_published' => $request->boolean('is_published') ? 1 : 0,
        ]);

        ActivityLog::record('brandCategory.update', 'brand_categories', $id, 'دسته‌بندی برند «' . $title . '» ویرایش شد');
        Session::flash('success', 'دسته‌بندی بروزرسانی شد.');

        return Response::redirect(url('/admin/brand-categories'));
    }

    public function destroy(int $id): Response
    {
        $category = BrandCategory::find($id);
        if ($category === null) {
            return $this->notFound('دسته‌بندی پیدا نشد.');
        }

        BrandCategory::deleteById($id);
        ActivityLog::record('brandCategory.delete', 'brand_categories', $id, 'دسته‌بندی برند «' . $category['title'] . '» حذف شد');
        Session::flash('success', 'دسته‌بندی حذف شد.');

        return Response::redirect(url('/admin/brand-categories'));
    }
}
