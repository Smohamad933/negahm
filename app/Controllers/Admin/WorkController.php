<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\ActivityLog;
use App\Models\Brand;
use App\Models\Service;
use App\Models\Work;
use App\Models\WorkCategory;
use App\Models\WorkImage;

final class WorkController extends AdminController
{
    public function index(Request $request): Response
    {
        $filters = [
            'brand_id'    => $request->integer('brand'),
            'category_id' => $request->integer('category'),
            'q'           => trim((string) $request->query('q', '')),
            'order'       => 'w.sort_order ASC, w.id DESC',
        ];

        return $this->view('admin.works.index', [
            'pageTitle'  => 'مدیریت نمونه‌کارها — پنل نگاه مدیا',
            'pager'      => Work::publishedList($filters, 15, max(1, $request->integer('page', 1))),
            'brands'     => Brand::adminList('', 500, 1)['data'],
            'categories' => WorkCategory::options(),
            'filters'    => $filters,
        ]);
    }

    public function create(): Response
    {
        return $this->view('admin.works.form', [
            'pageTitle'  => 'افزودن نمونه‌کار — پنل نگاه مدیا',
            'work'       => null,
            'brands'     => Brand::adminList('', 500, 1)['data'],
            'categories' => WorkCategory::options(),
            'services'   => Service::options(),
            'action'     => url('/admin/works'),
            'heading'    => 'افزودن نمونه‌کار جدید',
            'gallery'    => [],
        ]);
    }

    public function store(Request $request): Response
    {
        $data = $this->collect($request);

        $validator = Validator::make($data, [
            'title' => 'required|minlen:2|maxlen:190',
            'slug'  => 'nullable|slug|maxlen:190',
        ], ['title' => 'عنوان پروژه', 'slug' => 'آدرس یکتا (نامک)']);

        if ($validator->fails()) {
            return $this->withErrors($validator, url('/admin/works/create'), $request->all());
        }

        $prepared = Work::prepare($data);

        if ($request->hasFile('cover')) {
            $cover = $this->uploadFile($request->file('cover'), 'works');
            if ($cover['ok']) {
                $prepared['cover'] = $cover['path'];
            }
        }

        $prepared['created_at'] = date('Y-m-d H:i:s');
        $id = Work::create($prepared);

        $this->attachGallery($id, $request);

        ActivityLog::record('work.create', 'works', $id, 'نمونه‌کار «' . $prepared['title'] . '» ایجاد شد');
        Session::flash('success', 'نمونه‌کار «' . $prepared['title'] . '» ایجاد شد.');

        return Response::redirect(url('/admin/works/' . $id . '/edit'));
    }

    public function edit(int $id): Response
    {
        $work = Work::adminById($id);
        if ($work === null) {
            return $this->notFound('نمونه‌کار پیدا نشد.');
        }

        return $this->view('admin.works.form', [
            'pageTitle'  => 'ویرایش نمونه‌کار — پنل نگاه مدیا',
            'work'       => $work,
            'brands'     => Brand::adminList('', 500, 1)['data'],
            'categories' => WorkCategory::options(),
            'services'   => Service::options(),
            'action'     => url('/admin/works/' . $id),
            'heading'    => 'ویرایش نمونه‌کار: ' . $work['title'],
            'gallery'    => WorkImage::forWork($id),
        ]);
    }

    public function update(Request $request, int $id): Response
    {
        $work = Work::find($id);
        if ($work === null) {
            return $this->notFound('نمونه‌کار پیدا نشد.');
        }

        $data = $this->collect($request);

        $validator = Validator::make($data, [
            'title' => 'required|minlen:2|maxlen:190',
            'slug'  => 'nullable|slug|maxlen:190',
        ], ['title' => 'عنوان پروژه', 'slug' => 'آدرس یکتا (نامک)']);

        if ($validator->fails()) {
            return $this->withErrors($validator, url('/admin/works/' . $id . '/edit'), $request->all());
        }

        $prepared = Work::prepare($data, $id);

        if ($request->hasFile('cover')) {
            $cover = $this->uploadFile($request->file('cover'), 'works');
            if ($cover['ok']) {
                $prepared['cover'] = $cover['path'];
            }
        } elseif ($request->input('remove_cover')) {
            $prepared['cover'] = null;
        }

        Work::updateById($id, $prepared);
        $this->attachGallery($id, $request);

        ActivityLog::record('work.update', 'works', $id, 'نمونه‌کار «' . $prepared['title'] . '» ویرایش شد');
        Session::flash('success', 'تغییرات نمونه‌کار ذخیره شد.');

        return Response::redirect(url('/admin/works/' . $id . '/edit'));
    }

    public function addImages(Request $request, int $id): Response
    {
        if (Work::find($id) === null) {
            return $this->fail('نمونه‌کار پیدا نشد.', 404);
        }

        $count = count($this->attachGallery($id, $request));

        if ($count === 0) {
            return $this->fail('هیچ تصویری بارگذاری نشد.');
        }

        ActivityLog::record('work.gallery', 'works', $id, 'افزودن ' . $count . ' تصویر به گالری');

        if ($request->wantsJson()) {
            return $this->ok($count . ' تصویر اضافه شد', ['count' => $count]);
        }
        Session::flash('success', $count . ' تصویر به گالری اضافه شد.');
        return Response::redirect(url('/admin/works/' . $id . '/edit'));
    }

    public function deleteImage(Request $request, int $id): Response
    {
        $image = WorkImage::find($id);
        if ($image === null) {
            return $this->fail('تصویر پیدا نشد.', 404);
        }

        $workId = (int) $image['work_id'];
        WorkImage::remove($id);

        ActivityLog::record('work.gallery.delete', 'works', $workId, 'حذف یک تصویر از گالری');

        if ($request->wantsJson()) {
            return $this->ok('تصویر حذف شد');
        }
        Session::flash('success', 'تصویر حذف شد.');
        return Response::redirect(url('/admin/works/' . $workId . '/edit'));
    }

    public function destroy(int $id): Response
    {
        $work = Work::find($id);
        if ($work === null) {
            return $this->notFound('نمونه‌کار پیدا نشد.');
        }

        foreach (WorkImage::forWork($id) as $image) {
            WorkImage::remove((int) $image['id']);
        }

        Work::deleteById($id);
        ActivityLog::record('work.delete', 'works', $id, 'نمونه‌کار «' . $work['title'] . '» حذف شد');
        Session::flash('success', 'نمونه‌کار حذف شد.');

        return Response::redirect(url('/admin/works'));
    }

    public function toggle(int $id, string $column): Response
    {
        if (!in_array($column, ['is_published', 'is_featured'], true)) {
            return $this->fail('ستون نامعتبر است.');
        }
        if (!Work::toggle($id, $column)) {
            return $this->fail('نمونه‌کار پیدا نشد.', 404);
        }

        $work = Work::find($id);
        ActivityLog::record('work.toggle', 'works', $id, 'تغییر وضعیت «' . $column . '»');

        return $this->ok('وضعیت بروزرسانی شد', ['value' => (int) ($work[$column] ?? 0)]);
    }

    /** @return array<int,string> مسیر تصویرهای اضافه‌شده */
    private function attachGallery(int $workId, Request $request): array
    {
        if (!$request->hasFile('gallery')) {
            return [];
        }

        $paths   = $this->uploadMany($request->file('gallery'), 'works');
        $sort    = WorkImage::nextSort($workId);
        $stored  = [];

        foreach ($paths as $index => $path) {
            $alt = (string) ($request->input('gallery_alt')[$index] ?? '');
            WorkImage::create([
                'work_id'    => $workId,
                'path'       => $path,
                'alt'        => $alt !== '' ? $alt : null,
                'kind'       => 'image',
                'sort_order' => $sort + $index,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $stored[] = $path;
        }

        return $stored;
    }

    /** @return array<string,mixed> */
    private function collect(Request $request): array
    {
        return [
            'title'         => (string) $request->input('title', ''),
            'slug'          => (string) $request->input('slug', ''),
            'label_en'      => (string) $request->input('label_en', ''),
            'brand_id'      => (string) $request->input('brand_id', ''),
            'category_id'   => (string) $request->input('category_id', ''),
            'service_id'    => (string) $request->input('service_id', ''),
            'excerpt'       => (string) $request->input('excerpt', ''),
            'body'          => (string) $request->input('body', ''),
            'challenge'     => (string) $request->input('challenge', ''),
            'solution'      => (string) $request->input('solution', ''),
            'result'        => (string) $request->input('result', ''),
            'client_name'   => (string) $request->input('client_name', ''),
            'year'          => (string) $request->input('year', ''),
            'duration'      => (string) $request->input('duration', ''),
            'services_list' => (string) $request->input('services_list', ''),
            'video_url'     => (string) $request->input('video_url', ''),
            'link_url'      => (string) $request->input('link_url', ''),
            'tags'          => (string) $request->input('tags', ''),
            'is_featured'   => $request->boolean('is_featured'),
            'is_published'  => $request->boolean('is_published'),
            'sort_order'    => $request->integer('sort_order', 0),
            'published_at'  => (string) $request->input('published_at', ''),
            'seo_title'     => (string) $request->input('seo_title', ''),
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
