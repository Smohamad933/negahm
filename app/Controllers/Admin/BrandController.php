<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\ActivityLog;
use App\Models\Brand;
use App\Models\BrandCategory;
use App\Models\Work;

final class BrandController extends AdminController
{
    public function index(Request $request): Response
    {
        return $this->view('admin.brands.index', [
            'pageTitle'  => 'مدیریت برندها — پنل نگاه مدیا',
            'pager'      => Brand::adminList(trim((string) $request->query('q', '')), 15, max(1, $request->integer('page', 1))),
            'q'          => trim((string) $request->query('q', '')),
            'categories' => BrandCategory::options(),
        ]);
    }

    public function create(): Response
    {
        return $this->view('admin.brands.form', [
            'pageTitle'  => 'افزودن برند جدید — پنل نگاه مدیا',
            'brand'      => null,
            'categories' => BrandCategory::options(),
            'action'     => url('/admin/brands'),
            'heading'    => 'افزودن برند جدید',
        ]);
    }

    public function store(Request $request): Response
    {
        $data = $this->collect($request);

        $validator = Validator::make($data, [
            'name' => 'required|minlen:2|maxlen:190',
            'slug' => 'nullable|slug|maxlen:190',
        ], ['name' => 'نام برند', 'slug' => 'آدرس یکتا (نامک)']);

        if ($validator->fails()) {
            return $this->withErrors($validator, url('/admin/brands/create'), $request->all());
        }

        $prepared = Brand::prepare($data);

        if ($request->hasFile('logo')) {
            $logo = $this->uploadFile($request->file('logo'), 'brands');
            if ($logo['ok']) {
                $prepared['logo'] = $logo['path'];
            }
        }
        if ($request->hasFile('cover')) {
            $cover = $this->uploadFile($request->file('cover'), 'brands');
            if ($cover['ok']) {
                $prepared['cover'] = $cover['path'];
            }
        }

        $prepared['created_at'] = date('Y-m-d H:i:s');
        $id = Brand::create($prepared);

        ActivityLog::record('brand.create', 'brands', $id, 'برند «' . $prepared['name'] . '» ایجاد شد');
        Session::flash('success', 'برند «' . $prepared['name'] . '» با موفقیت ایجاد شد.');

        return Response::redirect(url('/admin/brands/' . $id . '/edit'));
    }

    public function edit(int $id): Response
    {
        $brand = Brand::adminById($id);
        if ($brand === null) {
            return $this->notFound('برند پیدا نشد.');
        }

        return $this->view('admin.brands.form', [
            'pageTitle'  => 'ویرایش برند — پنل نگاه مدیا',
            'brand'      => $brand,
            'categories' => BrandCategory::options(),
            'action'     => url('/admin/brands/' . $id),
            'heading'    => 'ویرایش برند: ' . $brand['name'],
            'works'      => Work::publishedList(['brand_id' => $id], 20, 1)['data'],
            'socials'    => Brand::decodeSocials($brand['socials'] ?? null),
        ]);
    }

    public function update(Request $request, int $id): Response
    {
        $brand = Brand::find($id);
        if ($brand === null) {
            return $this->notFound('برند پیدا نشد.');
        }

        $data = $this->collect($request);

        $validator = Validator::make($data, [
            'name' => 'required|minlen:2|maxlen:190',
            'slug' => 'nullable|slug|maxlen:190',
        ], ['name' => 'نام برند', 'slug' => 'آدرس یکتا (نامک)']);

        if ($validator->fails()) {
            return $this->withErrors($validator, url('/admin/brands/' . $id . '/edit'), $request->all());
        }

        $prepared = Brand::prepare($data, $id);

        if ($request->hasFile('logo')) {
            $logo = $this->uploadFile($request->file('logo'), 'brands');
            if ($logo['ok']) {
                $prepared['logo'] = $logo['path'];
            }
        } elseif ($request->input('remove_logo')) {
            $prepared['logo'] = null;
        }

        if ($request->hasFile('cover')) {
            $cover = $this->uploadFile($request->file('cover'), 'brands');
            if ($cover['ok']) {
                $prepared['cover'] = $cover['path'];
            }
        } elseif ($request->input('remove_cover')) {
            $prepared['cover'] = null;
        }

        Brand::updateById($id, $prepared);
        ActivityLog::record('brand.update', 'brands', $id, 'برند «' . $prepared['name'] . '» ویرایش شد');
        Session::flash('success', 'تغییرات برند ذخیره شد.');

        return Response::redirect(url('/admin/brands/' . $id . '/edit'));
    }

    public function destroy(int $id): Response
    {
        $brand = Brand::find($id);
        if ($brand === null) {
            return $this->notFound('برند پیدا نشد.');
        }

        Brand::deleteById($id);
        ActivityLog::record('brand.delete', 'brands', $id, 'برند «' . $brand['name'] . '» حذف شد');
        Session::flash('success', 'برند «' . $brand['name'] . '» حذف شد.');

        return Response::redirect(url('/admin/brands'));
    }

    public function toggle(int $id, string $column): Response
    {
        if (!in_array($column, ['is_published', 'is_featured', 'show_in_marquee', 'has_dedicated_page'], true)) {
            return $this->fail('ستون نامعتبر است.');
        }

        if (!Brand::toggle($id, $column)) {
            return $this->fail('برند پیدا نشد.', 404);
        }

        $brand = Brand::find($id);
        ActivityLog::record('brand.toggle', 'brands', $id, 'تغییر وضعیت «' . $column . '» برند «' . ($brand['name'] ?? $id) . '»');

        return $this->ok('وضعیت بروزرسانی شد', ['value' => (int) ($brand[$column] ?? 0)]);
    }

    /** @return array<string,mixed> */
    private function collect(Request $request): array
    {
        return [
            'name'               => (string) $request->input('name', ''),
            'name_en'            => (string) $request->input('name_en', ''),
            'slug'               => (string) $request->input('slug', ''),
            'category_id'        => (string) $request->input('category_id', ''),
            'excerpt'            => (string) $request->input('excerpt', ''),
            'body'               => (string) $request->input('body', ''),
            'website'            => (string) $request->input('website', ''),
            'location'           => (string) $request->input('location', ''),
            'industry'           => (string) $request->input('industry', ''),
            'started_at'         => (string) $request->input('started_at', ''),
            'tags'               => (string) $request->input('tags', ''),
            'video_url'          => (string) $request->input('video_url', ''),
            'socials'            => (array) $request->input('socials', []),
            'is_featured'        => $request->boolean('is_featured'),
            'show_in_marquee'    => $request->boolean('show_in_marquee'),
            'is_published'       => $request->boolean('is_published'),
            'has_dedicated_page' => $request->boolean('has_dedicated_page'),
            'sort_order'         => $request->integer('sort_order', 0),
            'seo_title'          => (string) $request->input('seo_title', ''),
            'seo_description'    => (string) $request->input('seo_description', ''),
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
