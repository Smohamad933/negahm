<?php

declare(strict_types=1);

namespace App\Controllers\Front;

use App\Controllers\FrontController;
use App\Core\Request;
use App\Core\Response;
use App\Models\Work;
use App\Models\WorkCategory;
use App\Models\WorkImage;

final class WorkController extends FrontController
{
    public function index(Request $request): Response
    {
        $filters = [
            'category' => $request->integer('category'),
            'q'        => trim((string) $request->query('q', '')),
            'order'    => 'w.sort_order ASC, w.id DESC',
        ];

        return $this->view('front.works', [
            'pageTitle'  => 'نمونه‌کارها — ' . setting('site_name', 'نگاه مدیا'),
            'seoTitle'   => (string) setting('works_seo_title', 'نمونه‌کارهای نگاه مدیا'),
            'seoDesc'    => (string) setting('works_seo_description', 'پروژه‌های هویت بصری، کمپین، تولید محتوا و طراحی وب که اجرا کرده‌ایم.'),
            'pager'      => Work::publishedList($filters, 9, max(1, $request->integer('page', 1))),
            'categories' => WorkCategory::published(),
            'activeCat'  => $filters['category'],
            'q'          => $filters['q'],
        ]);
    }

    public function category(Request $request, string $slug): Response
    {
        $category = WorkCategory::publishedBySlug($slug);
        if ($category === null) {
            return $this->notFound('دسته‌بندی پیدا نشد.');
        }

        return $this->view('front.works', [
            'pageTitle'  => $category['title'] . ' — نمونه‌کارها',
            'seoTitle'   => (string) $category['title'],
            'seoDesc'    => (string) ($category['description'] ?: ''),
            'pager'      => Work::publishedList(['category_id' => (int) $category['id']], 9, max(1, $request->integer('page', 1))),
            'categories' => WorkCategory::published(),
            'activeCat'  => (int) $category['id'],
            'q'          => '',
            'activeCategory' => $category,
        ]);
    }

    public function show(string $slug): Response
    {
        $work = Work::publishedBySlug($slug);
        if ($work === null) {
            return $this->notFound('پروژه‌ای با این آدرس پیدا نشد.');
        }

        Work::increaseViews((int) $work['id']);

        return $this->view('front.work', [
            'pageTitle'  => (string) ($work['seo_title'] ?: $work['title'] . ' — نمونه‌کار'),
            'seoTitle'   => (string) ($work['seo_title'] ?: ''),
            'seoDesc'    => (string) ($work['seo_description'] ?: excerpt((string) ($work['excerpt'] ?: $work['body']), 180)),
            'work'       => $work,
            'gallery'    => WorkImage::forWork((int) $work['id']),
            'related'    => Work::related((int) $work['id'], $work['category_id'] ? (int) $work['category_id'] : null, $work['brand_id'] ? (int) $work['brand_id'] : null, 3),
            'neighbours' => Work::neighbours((int) $work['id']),
            'services'   => Work::splitList($work['services_list'] ?? null),
        ]);
    }
}
