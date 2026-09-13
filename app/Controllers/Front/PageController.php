<?php

declare(strict_types=1);

namespace App\Controllers\Front;

use App\Controllers\FrontController;
use App\Core\Response;
use App\Models\Page;

final class PageController extends FrontController
{
    public function show(string $slug): Response
    {
        $page = Page::publishedBySlug($slug);
        if ($page === null) {
            return $this->notFound('صفحه پیدا نشد.');
        }

        return $this->view('front.page', [
            'pageTitle' => (string) ($page['seo_title'] ?: $page['title']),
            'seoTitle'  => (string) ($page['seo_title'] ?: ''),
            'seoDesc'   => (string) ($page['seo_description'] ?: excerpt((string) $page['body'], 180)),
            'page'      => $page,
        ]);
    }
}
