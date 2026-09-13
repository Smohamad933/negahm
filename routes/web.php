<?php

declare(strict_types=1);

/**
 * تعریف مسیرهای برنامه — نگاه مدیا
 * @return App\Core\Router
 */

use App\Controllers\Admin;
use App\Controllers\Front;
use App\Core\Router;

$router = new Router();

/* ------------------------------------------------------------------ سایت اصلی */
$router->group('', [], static function (Router $r): void {
    $r->get('/', [Front\HomeController::class, 'index'], [], 'home');

    $r->get('/brands', [Front\BrandController::class, 'index'], [], 'brands.index');
    $r->get('/brands/{slug}', [Front\BrandController::class, 'show'], [], 'brands.show');

    $r->get('/works', [Front\WorkController::class, 'index'], [], 'works.index');
    $r->get('/works/category/{slug}', [Front\WorkController::class, 'category'], [], 'works.category');
    $r->get('/works/{slug}', [Front\WorkController::class, 'show'], [], 'works.show');

    $r->get('/services', [Front\ServiceController::class, 'index'], [], 'services.index');
    $r->get('/services/{slug}', [Front\ServiceController::class, 'show'], [], 'services.show');

    $r->get('/about', [Front\AboutController::class, 'index'], [], 'about');
    $r->get('/contact', [Front\ContactController::class, 'index'], [], 'contact');
    $r->post('/contact', [Front\ContactController::class, 'store'], ['throttle', 'csrf'], 'contact.store');

    $r->get('/blog', [Front\BlogController::class, 'index'], [], 'blog.index');
    $r->get('/blog/category/{slug}', [Front\BlogController::class, 'category'], [], 'blog.category');
    $r->get('/blog/{slug}', [Front\BlogController::class, 'show'], [], 'blog.show');

    $r->get('/faq', [Front\FaqController::class, 'index'], [], 'faq');
    $r->get('/search', [Front\SearchController::class, 'index'], [], 'search');
    $r->get('/sitemap.xml', [Front\SitemapController::class, 'index'], [], 'sitemap');
    $r->get('/robots.txt', [Front\SitemapController::class, 'robots'], [], 'robots');
    $r->post('/subscribe', [Front\ContactController::class, 'subscribe'], ['throttle', 'csrf'], 'subscribe');
    $r->get('/p/{slug}', [Front\PageController::class, 'show'], [], 'page.show');
});

/* ------------------------------------------------------------------ پنل مدیریت */
$router->group('/admin', ['guest'], static function (Router $r): void {
    $r->get('/login', [Admin\AuthController::class, 'showLogin'], [], 'admin.login');
    $r->post('/login', [Admin\AuthController::class, 'login'], ['throttle', 'csrf'], 'admin.login.post');
});

$router->group('/admin', ['auth'], static function (Router $r): void {
    $r->post('/logout', [Admin\AuthController::class, 'logout'], ['csrf'], 'admin.logout');
    $r->get('/', [Admin\DashboardController::class, 'index'], [], 'admin.dashboard');
    $r->get('/profile', [Admin\AuthController::class, 'profile'], [], 'admin.profile');
    $r->post('/profile', [Admin\AuthController::class, 'updateProfile'], ['csrf'], 'admin.profile.update');

    /* برندها */
    $r->get('/brands', [Admin\BrandController::class, 'index'], [], 'admin.brands.index');
    $r->get('/brands/create', [Admin\BrandController::class, 'create'], [], 'admin.brands.create');
    $r->post('/brands', [Admin\BrandController::class, 'store'], ['csrf'], 'admin.brands.store');
    $r->get('/brands/{id:\d+}/edit', [Admin\BrandController::class, 'edit'], [], 'admin.brands.edit');
    $r->post('/brands/{id:\d+}', [Admin\BrandController::class, 'update'], ['csrf'], 'admin.brands.update');
    $r->post('/brands/{id:\d+}/delete', [Admin\BrandController::class, 'destroy'], ['csrf'], 'admin.brands.delete');
    $r->post('/brands/{id:\d+}/toggle/{column}', [Admin\BrandController::class, 'toggle'], ['csrf'], 'admin.brands.toggle');

    /* دسته‌بندی برندها */
    $r->get('/brand-categories', [Admin\BrandCategoryController::class, 'index'], [], 'admin.brandCategories.index');
    $r->post('/brand-categories', [Admin\BrandCategoryController::class, 'store'], ['csrf'], 'admin.brandCategories.store');
    $r->post('/brand-categories/{id:\d+}', [Admin\BrandCategoryController::class, 'update'], ['csrf'], 'admin.brandCategories.update');
    $r->post('/brand-categories/{id:\d+}/delete', [Admin\BrandCategoryController::class, 'destroy'], ['csrf'], 'admin.brandCategories.delete');

    /* نمونه‌کارها */
    $r->get('/works', [Admin\WorkController::class, 'index'], [], 'admin.works.index');
    $r->get('/works/create', [Admin\WorkController::class, 'create'], [], 'admin.works.create');
    $r->post('/works', [Admin\WorkController::class, 'store'], ['csrf'], 'admin.works.store');
    $r->get('/works/{id:\d+}/edit', [Admin\WorkController::class, 'edit'], [], 'admin.works.edit');
    $r->post('/works/{id:\d+}', [Admin\WorkController::class, 'update'], ['csrf'], 'admin.works.update');
    $r->post('/works/{id:\d+}/delete', [Admin\WorkController::class, 'destroy'], ['csrf'], 'admin.works.delete');
    $r->post('/works/{id:\d+}/toggle/{column}', [Admin\WorkController::class, 'toggle'], ['csrf'], 'admin.works.toggle');
    $r->post('/works/{id:\d+}/images', [Admin\WorkController::class, 'addImages'], ['csrf'], 'admin.works.images.add');
    $r->post('/work-images/{id:\d+}/delete', [Admin\WorkController::class, 'deleteImage'], ['csrf'], 'admin.works.images.delete');

    /* دسته‌بندی نمونه‌کار */
    $r->get('/work-categories', [Admin\WorkCategoryController::class, 'index'], [], 'admin.workCategories.index');
    $r->post('/work-categories', [Admin\WorkCategoryController::class, 'store'], ['csrf'], 'admin.workCategories.store');
    $r->post('/work-categories/{id:\d+}', [Admin\WorkCategoryController::class, 'update'], ['csrf'], 'admin.workCategories.update');
    $r->post('/work-categories/{id:\d+}/delete', [Admin\WorkCategoryController::class, 'destroy'], ['csrf'], 'admin.workCategories.delete');

    /* خدمات */
    $r->get('/services', [Admin\ServiceController::class, 'index'], [], 'admin.services.index');
    $r->get('/services/create', [Admin\ServiceController::class, 'create'], [], 'admin.services.create');
    $r->post('/services', [Admin\ServiceController::class, 'store'], ['csrf'], 'admin.services.store');
    $r->get('/services/{id:\d+}/edit', [Admin\ServiceController::class, 'edit'], [], 'admin.services.edit');
    $r->post('/services/{id:\d+}', [Admin\ServiceController::class, 'update'], ['csrf'], 'admin.services.update');
    $r->post('/services/{id:\d+}/delete', [Admin\ServiceController::class, 'destroy'], ['csrf'], 'admin.services.delete');

    /* محتوا: تیم، نظرات، آمار، مراحل، اسلایدر، سوالات */
    $r->get('/team', [Admin\ContentController::class, 'team'], [], 'admin.team.index');
    $r->post('/team', [Admin\ContentController::class, 'storeTeam'], ['csrf'], 'admin.team.store');
    $r->post('/team/{id:\d+}/delete', [Admin\ContentController::class, 'destroyTeam'], ['csrf'], 'admin.team.delete');

    $r->get('/testimonials', [Admin\ContentController::class, 'testimonials'], [], 'admin.testimonials.index');
    $r->post('/testimonials', [Admin\ContentController::class, 'storeTestimonial'], ['csrf'], 'admin.testimonials.store');
    $r->post('/testimonials/{id:\d+}/delete', [Admin\ContentController::class, 'destroyTestimonial'], ['csrf'], 'admin.testimonials.delete');

    $r->get('/stats', [Admin\ContentController::class, 'stats'], [], 'admin.stats.index');
    $r->post('/stats', [Admin\ContentController::class, 'storeStat'], ['csrf'], 'admin.stats.store');
    $r->post('/stats/{id:\d+}/delete', [Admin\ContentController::class, 'destroyStat'], ['csrf'], 'admin.stats.delete');

    $r->get('/process', [Admin\ContentController::class, 'process'], [], 'admin.process.index');
    $r->post('/process', [Admin\ContentController::class, 'storeProcess'], ['csrf'], 'admin.process.store');
    $r->post('/process/{id:\d+}/delete', [Admin\ContentController::class, 'destroyProcess'], ['csrf'], 'admin.process.delete');

    $r->get('/sliders', [Admin\ContentController::class, 'sliders'], [], 'admin.sliders.index');
    $r->post('/sliders', [Admin\ContentController::class, 'storeSlider'], ['csrf'], 'admin.sliders.store');
    $r->post('/sliders/{id:\d+}/delete', [Admin\ContentController::class, 'destroySlider'], ['csrf'], 'admin.sliders.delete');

    $r->get('/faqs', [Admin\ContentController::class, 'faqs'], [], 'admin.faqs.index');
    $r->post('/faqs', [Admin\ContentController::class, 'storeFaq'], ['csrf'], 'admin.faqs.store');
    $r->post('/faqs/{id:\d+}/delete', [Admin\ContentController::class, 'destroyFaq'], ['csrf'], 'admin.faqs.delete');

    /* وبلاگ */
    $r->get('/posts', [Admin\PostController::class, 'index'], [], 'admin.posts.index');
    $r->get('/posts/create', [Admin\PostController::class, 'create'], [], 'admin.posts.create');
    $r->post('/posts', [Admin\PostController::class, 'store'], ['csrf'], 'admin.posts.store');
    $r->get('/posts/{id:\d+}/edit', [Admin\PostController::class, 'edit'], [], 'admin.posts.edit');
    $r->post('/posts/{id:\d+}', [Admin\PostController::class, 'update'], ['csrf'], 'admin.posts.update');
    $r->post('/posts/{id:\d+}/delete', [Admin\PostController::class, 'destroy'], ['csrf'], 'admin.posts.delete');
    $r->get('/post-categories', [Admin\PostController::class, 'categories'], [], 'admin.postCategories.index');
    $r->post('/post-categories', [Admin\PostController::class, 'storeCategory'], ['csrf'], 'admin.postCategories.store');
    $r->post('/post-categories/{id:\d+}', [Admin\PostController::class, 'updateCategory'], ['csrf'], 'admin.postCategories.update');
    $r->post('/post-categories/{id:\d+}/delete', [Admin\PostController::class, 'destroyCategory'], ['csrf'], 'admin.postCategories.delete');

    /* پیام‌ها */
    $r->get('/messages', [Admin\MessageController::class, 'index'], [], 'admin.messages.index');
    $r->get('/messages/{id:\d+}', [Admin\MessageController::class, 'show'], [], 'admin.messages.show');
    $r->post('/messages/{id:\d+}/read', [Admin\MessageController::class, 'markRead'], ['csrf'], 'admin.messages.read');
    $r->post('/messages/{id:\d+}/archive', [Admin\MessageController::class, 'archive'], ['csrf'], 'admin.messages.archive');
    $r->post('/messages/{id:\d+}/delete', [Admin\MessageController::class, 'destroy'], ['csrf'], 'admin.messages.delete');

    /* رسانه‌ها */
    $r->get('/media', [Admin\MediaController::class, 'index'], [], 'admin.media.index');
    $r->post('/media', [Admin\MediaController::class, 'store'], ['csrf'], 'admin.media.store');
    $r->post('/media/{id:\d+}/delete', [Admin\MediaController::class, 'destroy'], ['csrf'], 'admin.media.delete');

    /* صفحات */
    $r->get('/pages', [Admin\PageController::class, 'index'], [], 'admin.pages.index');
    $r->get('/pages/create', [Admin\PageController::class, 'create'], [], 'admin.pages.create');
    $r->post('/pages', [Admin\PageController::class, 'store'], ['csrf'], 'admin.pages.store');
    $r->get('/pages/{id:\d+}/edit', [Admin\PageController::class, 'edit'], [], 'admin.pages.edit');
    $r->post('/pages/{id:\d+}', [Admin\PageController::class, 'update'], ['csrf'], 'admin.pages.update');
    $r->post('/pages/{id:\d+}/delete', [Admin\PageController::class, 'destroy'], ['csrf'], 'admin.pages.delete');

    /* منوها */
    $r->get('/menu', [Admin\MenuController::class, 'index'], ['admin'], 'admin.menu.index');
    $r->post('/menu', [Admin\MenuController::class, 'store'], ['csrf', 'admin'], 'admin.menu.store');
    $r->post('/menu/reorder', [Admin\MenuController::class, 'reorder'], ['csrf', 'admin'], 'admin.menu.reorder');
    $r->post('/menu/{id:\d+}', [Admin\MenuController::class, 'update'], ['csrf', 'admin'], 'admin.menu.update');
    $r->post('/menu/{id:\d+}/toggle/{column}', [Admin\MenuController::class, 'toggle'], ['csrf', 'admin'], 'admin.menu.toggle');
    $r->post('/menu/{id:\d+}/delete', [Admin\MenuController::class, 'destroy'], ['csrf', 'admin'], 'admin.menu.delete');

    /* فونت‌ها */
    $r->get('/fonts', [Admin\FontController::class, 'index'], ['admin'], 'admin.fonts.index');
    $r->post('/fonts', [Admin\FontController::class, 'store'], ['csrf', 'admin'], 'admin.fonts.store');
    $r->post('/fonts/reset', [Admin\FontController::class, 'reset'], ['csrf', 'admin'], 'admin.fonts.reset');
    $r->post('/fonts/{id:\d+}/activate', [Admin\FontController::class, 'activate'], ['csrf', 'admin'], 'admin.fonts.activate');
    $r->post('/fonts/{id:\d+}/delete', [Admin\FontController::class, 'destroy'], ['csrf', 'admin'], 'admin.fonts.delete');

    /* آمار بازدید */
    $r->get('/analytics', [Admin\AnalyticsController::class, 'index'], [], 'admin.analytics');
    $r->post('/analytics/prune', [Admin\AnalyticsController::class, 'prune'], ['csrf', 'admin'], 'admin.analytics.prune');

    /* کاربران */
    $r->get('/users', [Admin\UserController::class, 'index'], ['admin'], 'admin.users.index');
    $r->post('/users', [Admin\UserController::class, 'store'], ['csrf', 'admin'], 'admin.users.store');
    $r->post('/users/{id:\d+}', [Admin\UserController::class, 'update'], ['csrf', 'admin'], 'admin.users.update');
    $r->post('/users/{id:\d+}/delete', [Admin\UserController::class, 'destroy'], ['csrf', 'admin'], 'admin.users.delete');

    /* تنظیمات و گزارش‌ها */
    $r->get('/settings', [Admin\SettingController::class, 'index'], ['admin'], 'admin.settings.index');
    $r->post('/settings', [Admin\SettingController::class, 'update'], ['csrf', 'admin'], 'admin.settings.update');
    $r->get('/activity', [Admin\DashboardController::class, 'activity'], [], 'admin.activity');
    $r->get('/subscribers', [Admin\DashboardController::class, 'subscribers'], [], 'admin.subscribers');
});

return $router;
