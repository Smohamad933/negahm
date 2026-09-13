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
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Service;
use App\Models\Work;
use App\Models\WorkCategory;

/**
 * مدیریت منوی سایت — افزودن، حذف، ویرایش، ترتیب و کنترل نمایش در موبایل/دسکتاپ
 */
final class MenuController extends AdminController
{
    /** فهرست رکوردهایی که می‌توان به منو وصل کرد */
    private function options(): array
    {
        return [
            'pages'           => Page::all('sort_order ASC, id ASC'),
            'services'        => Service::all('sort_order ASC, id ASC'),
            'works'           => Work::all('sort_order ASC, id DESC'),
            'workCategories'  => WorkCategory::all('sort_order ASC, id ASC'),
            'brands'          => Brand::all('sort_order ASC, id DESC'),
            'brandCategories' => BrandCategory::all('sort_order ASC, id ASC'),
            'posts'           => Post::all('id DESC'),
            'postCategories'  => PostCategory::all('sort_order ASC, id ASC'),
        ];
    }

    public function index(Request $request): Response
    {
        $position = $request->query('position', 'header') === 'footer' ? 'footer' : 'header';

        return $this->view('admin.menu.index', [
            'pageTitle' => 'مدیریت منو — پنل نگاه مدیا',
            'position'  => $position,
            'items'     => MenuItem::adminList($position),
            'types'     => MenuItem::TYPES,
            'routes'    => MenuItem::ROUTES,
            'positions' => MenuItem::POSITIONS,
            'options'   => $this->options(),
        ]);
    }

    public function store(Request $request): Response
    {
        $data = $this->collect($request);

        $validator = Validator::make($data, [
            'type'  => 'required',
            'title' => 'nullable|maxlen:190',
            'url'   => 'nullable|maxlen:255',
        ], ['type' => 'نوع آیتم', 'title' => 'عنوان', 'url' => 'آدرس']);

        if ($validator->fails()) {
            Session::flash('danger', $validator->firstError());

            return Response::redirect(url('/admin/menu?position=' . urlencode((string) $data['position'])));
        }

        $prepared = MenuItem::prepare($data);
        if ($prepared['title'] === '' && MenuItem::resolveTitle($prepared + ['id' => 0]) === '') {
            Session::flash('danger', 'عنوان آیتم منو نمی‌تواند خالی باشد.');

            return Response::redirect(url('/admin/menu?position=' . urlencode((string) $prepared['position'])));
        }

        $prepared['sort_order'] = $request->integer('sort_order', MenuItem::nextSort((string) $prepared['position']));
        $prepared['created_at'] = MenuItem::now();

        $id = MenuItem::create($prepared);
        ActivityLog::record('menu.create', 'menu_items', $id, 'آیتم منو «' . $prepared['title'] . '» اضافه شد');
        Session::flash('success', 'آیتم به منو اضافه شد.');

        return Response::redirect(url('/admin/menu?position=' . urlencode((string) $prepared['position'])));
    }

    public function update(Request $request, int $id): Response
    {
        $item = MenuItem::find($id);
        if ($item === null) {
            return $this->notFound('آیتم منو پیدا نشد.');
        }

        $data     = $this->collect($request);
        $prepared = MenuItem::prepare($data, $id);

        if (trim((string) $prepared['title']) === '' && (int) $prepared['reference_id'] === 0 && $prepared['type'] !== 'route') {
            Session::flash('danger', 'عنوان آیتم منو نمی‌تواند خالی باشد.');

            return Response::redirect(url('/admin/menu?position=' . urlencode((string) $item['position'])));
        }

        $prepared['updated_at'] = MenuItem::now();
        MenuItem::updateById($id, $prepared);

        ActivityLog::record('menu.update', 'menu_items', $id, 'ویرایش آیتم منو');
        Session::flash('success', 'آیتم منو به‌روز شد.');

        return Response::redirect(url('/admin/menu?position=' . urlencode((string) $prepared['position'])));
    }

    public function destroy(int $id): Response
    {
        $item = MenuItem::find($id);
        if ($item === null) {
            return $this->notFound('آیتم منو پیدا نشد.');
        }

        // فرزندان یک آیتم حذف‌شده به سطح بالا منتقل می‌شوند تا منو نشکند
        foreach (MenuItem::all() as $child) {
            if ((int) ($child['parent_id'] ?? 0) === $id) {
                MenuItem::updateById((int) $child['id'], ['parent_id' => null, 'updated_at' => MenuItem::now()]);
            }
        }

        MenuItem::deleteById($id);
        ActivityLog::record('menu.delete', 'menu_items', $id, 'حذف آیتم منو «' . $item['title'] . '»');
        Session::flash('success', 'آیتم منو حذف شد.');

        return Response::redirect(url('/admin/menu?position=' . urlencode((string) $item['position'])));
    }

    public function toggle(int $id, string $column): Response
    {
        $allowed = ['is_active', 'show_desktop', 'show_mobile', 'opens_new'];
        if (!in_array($column, $allowed, true) || MenuItem::find($id) === null) {
            return $this->notFound('آیتم منو پیدا نشد.');
        }

        MenuItem::toggle($id, $column);
        Session::flash('success', 'وضعیت به‌روز شد.');

        return Response::redirect(url('/admin/menu'));
    }

    /** ذخیره‌ی ترتیب جدید آیتم‌ها (کشیدن و رها کردن در پنل) */
    public function reorder(Request $request): Response
    {
        $ids = (array) $request->input('order', []);
        if ($ids !== []) {
            MenuItem::reorder($ids);
            Session::flash('success', 'ترتیب منو ذخیره شد.');
        }

        return Response::redirect(url('/admin/menu'));
    }

    /**
     * خواندن داده‌ی فرم.
     *
     * فرم برای هر «نوع» یک فیلد جدا دارد (چون انتخاب‌ها هم‌زمان در DOM هستند)،
     * پس اینجا بر اساس نوع انتخاب‌شده، فیلد درست را برمی‌داریم.
     *
     * @return array<string,mixed>
     */
    private function collect(Request $request): array
    {
        $type = (string) $request->input('type', 'custom');

        return [
            'title'        => (string) $request->input('title', ''),
            'type'         => $type,
            'url'          => $type === 'route'
                ? (string) $request->input('route_url', '/')
                : (string) $request->input('url', ''),
            'reference_id' => $request->integer('ref_' . $type, 0),
            'position'     => (string) $request->input('position', 'header'),
            'parent_id'    => $request->integer('parent_id', 0),
            'show_desktop' => $request->boolean('show_desktop'),
            'show_mobile'  => $request->boolean('show_mobile'),
            'opens_new'    => $request->boolean('opens_new'),
            'is_active'    => $request->boolean('is_active'),
            'sort_order'   => $request->integer('sort_order', 0),
        ];
    }
}
