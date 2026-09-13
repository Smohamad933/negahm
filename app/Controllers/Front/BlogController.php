<?php

declare(strict_types=1);

namespace App\Controllers\Front;

use App\Controllers\FrontController;
use App\Core\Request;
use App\Core\Response;
use App\Models\Post;
use App\Models\PostCategory;

final class BlogController extends FrontController
{
    public function index(Request $request): Response
    {
        return $this->view('front.blog', [
            'pageTitle'  => 'بلاگ — ' . setting('site_name', 'نگاه مدیا'),
            'seoTitle'   => (string) setting('blog_seo_title', 'بلاگ نگاه مدیا'),
            'seoDesc'    => (string) setting('blog_seo_description', 'یادداشت‌هایی درباره برندینگ، تبلیغات و طراحی.'),
            'pager'      => Post::publishedList(['q' => trim((string) $request->query('q', ''))], 9, max(1, $request->integer('page', 1))),
            'categories' => PostCategory::withCounts(),
            'tags'       => Post::popularTags(12),
            'q'          => trim((string) $request->query('q', '')),
            'activeCat'  => null,
        ]);
    }

    public function category(Request $request, string $slug): Response
    {
        $category = PostCategory::bySlug($slug);
        if ($category === null) {
            return $this->notFound('دسته‌بندی پیدا نشد.');
        }

        return $this->view('front.blog', [
            'pageTitle'  => $category['title'] . ' — بلاگ',
            'seoTitle'   => (string) $category['title'],
            'seoDesc'    => (string) ($category['description'] ?: ''),
            'pager'      => Post::publishedList(['category_id' => (int) $category['id']], 9, max(1, $request->integer('page', 1))),
            'categories' => PostCategory::withCounts(),
            'tags'       => Post::popularTags(12),
            'q'          => '',
            'activeCat'  => $category,
        ]);
    }

    public function show(string $slug): Response
    {
        $post = Post::publishedBySlug($slug);
        if ($post === null) {
            return $this->notFound('مقاله‌ای با این آدرس پیدا نشد.');
        }

        Post::increaseViews((int) $post['id']);

        return $this->view('front.post', [
            'pageTitle'  => (string) ($post['seo_title'] ?: $post['title']),
            'seoTitle'   => (string) ($post['seo_title'] ?: ''),
            'seoDesc'    => (string) ($post['seo_description'] ?: excerpt((string) ($post['excerpt'] ?: $post['body']), 180)),
            'post'       => $post,
            'related'    => Post::related((int) $post['id'], $post['category_id'] ? (int) $post['category_id'] : null, 3),
            'latest'     => Post::latest(4),
            'tags'       => \App\Models\Work::splitList($post['tags'] ?? null),
        ]);
    }
}
