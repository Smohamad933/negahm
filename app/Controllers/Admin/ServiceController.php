<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\ActivityLog;
use App\Models\Service;

final class ServiceController extends AdminController
{
    public function index(): Response
    {
        return $this->view('admin.services.index', [
            'pageTitle' => 'مدیریت خدمات — پنل نگاه مدیا',
            'services'  => Service::all('sort_order ASC, id ASC'),
        ]);
    }

    public function create(): Response
    {
        return $this->view('admin.services.form', [
            'pageTitle' => 'افزودن خدمت — پنل نگاه مدیا',
            'service'   => null,
            'items'     => [],
            'action'    => url('/admin/services'),
            'heading'   => 'افزودن خدمت جدید',
        ]);
    }

    public function store(Request $request): Response
    {
        $data = $this->collect($request);

        $validator = Validator::make($data, [
            'title' => 'required|minlen:2|maxlen:190',
            'slug'  => 'nullable|slug|maxlen:190',
        ], ['title' => 'عنوان خدمت', 'slug' => 'آدرس یکتا (نامک)']);

        if ($validator->fails()) {
            return $this->withErrors($validator, url('/admin/services/create'), $request->all());
        }

        $prepared = Service::prepare($data);

        if ($request->hasFile('image')) {
            $image = $this->uploadFile($request->file('image'), 'services');
            if ($image['ok']) {
                $prepared['image'] = $image['path'];
            }
        }

        $prepared['created_at'] = date('Y-m-d H:i:s');
        $id = Service::create($prepared);

        ActivityLog::record('service.create', 'services', $id, 'خدمت «' . $prepared['title'] . '» ایجاد شد');
        Session::flash('success', 'خدمت «' . $prepared['title'] . '» ایجاد شد.');

        return Response::redirect(url('/admin/services/' . $id . '/edit'));
    }

    public function edit(int $id): Response
    {
        $service = Service::find($id);
        if ($service === null) {
            return $this->notFound('خدمت پیدا نشد.');
        }

        return $this->view('admin.services.form', [
            'pageTitle' => 'ویرایش خدمت — پنل نگاه مدیا',
            'service'   => $service,
            'items'     => Service::decodeItems($service['items'] ?? null),
            'action'    => url('/admin/services/' . $id),
            'heading'   => 'ویرایش خدمت: ' . $service['title'],
        ]);
    }

    public function update(Request $request, int $id): Response
    {
        $service = Service::find($id);
        if ($service === null) {
            return $this->notFound('خدمت پیدا نشد.');
        }

        $data = $this->collect($request);

        $validator = Validator::make($data, [
            'title' => 'required|minlen:2|maxlen:190',
            'slug'  => 'nullable|slug|maxlen:190',
        ], ['title' => 'عنوان خدمت', 'slug' => 'آدرس یکتا (نامک)']);

        if ($validator->fails()) {
            return $this->withErrors($validator, url('/admin/services/' . $id . '/edit'), $request->all());
        }

        $prepared = Service::prepare($data, $id);

        if ($request->hasFile('image')) {
            $image = $this->uploadFile($request->file('image'), 'services');
            if ($image['ok']) {
                $prepared['image'] = $image['path'];
            }
        } elseif ($request->input('remove_image')) {
            $prepared['image'] = null;
        }

        Service::updateById($id, $prepared);
        ActivityLog::record('service.update', 'services', $id, 'خدمت «' . $prepared['title'] . '» ویرایش شد');
        Session::flash('success', 'تغییرات خدمت ذخیره شد.');

        return Response::redirect(url('/admin/services/' . $id . '/edit'));
    }

    public function destroy(int $id): Response
    {
        $service = Service::find($id);
        if ($service === null) {
            return $this->notFound('خدمت پیدا نشد.');
        }

        Service::deleteById($id);
        ActivityLog::record('service.delete', 'services', $id, 'خدمت «' . $service['title'] . '» حذف شد');
        Session::flash('success', 'خدمت حذف شد.');

        return Response::redirect(url('/admin/services'));
    }

    /** @return array<string,mixed> */
    private function collect(Request $request): array
    {
        return [
            'title'           => (string) $request->input('title', ''),
            'slug'            => (string) $request->input('slug', ''),
            'label_en'        => (string) $request->input('label_en', ''),
            'icon'            => (string) $request->input('icon', ''),
            'number'          => (string) $request->input('number', ''),
            'excerpt'         => (string) $request->input('excerpt', ''),
            'body'            => (string) $request->input('body', ''),
            'items'           => (array) $request->input('items', []),
            'price_note'      => (string) $request->input('price_note', ''),
            'is_featured'     => $request->boolean('is_featured'),
            'is_published'    => $request->boolean('is_published'),
            'sort_order'      => $request->integer('sort_order', 0),
            'seo_title'       => (string) $request->input('seo_title', ''),
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
