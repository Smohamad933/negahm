<?php

declare(strict_types=1);

namespace App\Controllers\Front;

use App\Controllers\FrontController;
use App\Core\Request;
use App\Core\Response;
use App\Models\Brand;
use App\Models\BrandCategory;
use App\Models\Work;

final class BrandController extends FrontController
{
    public function index(Request $request): Response
    {
        $categoryId = $request->integer('category');
        $search     = trim((string) $request->query('q', ''));
        $page       = max(1, $request->integer('page', 1));

        return $this->view('front.brands', [
            'pageTitle'   => 'برندها و مشتریان — ' . setting('site_name', 'نگاه مدیا'),
            'seoTitle'    => (string) setting('brands_seo_title', 'برندهایی که به ما اعتماد کردند'),
            'seoDesc'     => (string) setting('brands_seo_description', 'فهرست برندها و سازمان‌هایی که با نگاه مدیا همکاری کرده‌اند.'),
            'pager'       => Brand::publishedList($categoryId ?: null, $search, 12, $page),
            'categories'  => BrandCategory::published(),
            'activeCat'   => $categoryId,
            'q'           => $search,
            'marquee'     => Brand::marquee(40),
        ]);
    }

    public function show(Request $request, string $slug): Response
    {
        $brand = Brand::publishedBySlug($slug);

        if ($brand === null || (int) $brand['has_dedicated_page'] !== 1) {
            return $this->notFound('این برند صفحه اختصاصی ندارد یا منتشر نشده است.');
        }

        Brand::increaseViews((int) $brand['id']);

        $works = Work::publishedList([
            'brand_id' => (int) $brand['id'],
            'order'    => 'w.sort_order ASC, w.id DESC',
        ], 12, max(1, $request->integer('page', 1)));

        return $this->view('front.brand', [
            'pageTitle'  => (string) ($brand['seo_title'] ?: $brand['name'] . ' — همکاری با ' . setting('site_name', 'نگاه مدیا')),
            'seoTitle'   => (string) ($brand['seo_title'] ?: ''),
            'seoDesc'    => (string) ($brand['seo_description'] ?: excerpt((string) ($brand['excerpt'] ?: $brand['body']), 180)),
            'brand'      => $brand,
            'works'      => $works,
            'related'    => Brand::related((int) $brand['id'], $brand['category_id'] ? (int) $brand['category_id'] : null, 4),
            'socials'    => Brand::decodeSocials($brand['socials'] ?? null),
        ]);
    }
}
