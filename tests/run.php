<?php

/**
 * نگاه مدیا — مجموعه تست خودکار
 *
 * اجرا:  cd tools && node test.mjs
 *
 * این تست‌ها برنامه را واقعاً اجرا می‌کنند: درخواست‌ها از Router عبور
 * می‌کنند، میان‌افزارها، کنترلرها، مدل‌ها و قالب‌ها کار می‌کنند و
 * خروجی HTML بررسی می‌شود.
 *
 * پایگاه داده تست SQLite است (فقط برای تست، چون سرور MySQL در دسترس
 * نیست)؛ اسکیمای تست از روی خود database/schema.sql ساخته می‌شود.
 * محیط واقعی سایت MySQL/MariaDB است.
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/helpers/sqlite.php';

$root   = dirname(__DIR__);
$dbFile = sys_get_temp_dir() . '/negahm-test.sqlite';

build_test_database($root, $dbFile);

file_put_contents($root . '/.env', implode("\n", [
    'APP_NAME="نگاه مدیا"',
    'APP_ENV=testing',
    'APP_DEBUG=true',
    'APP_URL=http://localhost',
    'APP_TIMEZONE=Asia/Tehran',
    'APP_KEY=test-key-0123456789abcdef',
    'DB_DRIVER=sqlite',
    'DB_DATABASE=' . $dbFile,
    'UPLOAD_MAX_SIZE=8388608',
]) . "\n");

require $root . '/app/Core/App.php';
\App\Core\App::boot($root);

$router = require $root . '/routes/web.php';

/* ---------------------------------------------------------- ابزار تست */
$passed   = 0;
$failed   = 0;
$failures = [];

function it(string $name, callable $fn): void
{
    global $passed, $failed, $failures;
    try {
        $fn();
        $passed++;
        echo "  \033[32m✓\033[0m {$name}\n";
    } catch (Throwable $e) {
        $failed++;
        $failures[] = $name . ' → ' . $e->getMessage() . '  @ ' . basename($e->getFile()) . ':' . $e->getLine();
        echo "  \033[31m✗ {$name}\033[0m\n      " . $e->getMessage() . "\n";
    }
}

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function assertSame(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . ' — انتظار: ' . var_export($expected, true) . ' / دریافت: ' . var_export($actual, true));
    }
}

function assertContains(string $needle, string $haystack, string $message): void
{
    if (!str_contains($haystack, $needle)) {
        throw new RuntimeException($message . ' — عبارت پیدا نشد: «' . $needle . '»');
    }
}

/** اجرای یک درخواست واقعی از طریق Router */
function request(string $method, string $path, array $post = [], array $query = [], array $files = []): array
{
    global $router;

    $_SERVER['REQUEST_METHOD'] = $method;
    $_SERVER['REQUEST_URI']    = $path . ($query !== [] ? '?' . http_build_query($query) : '');
    $_GET                      = $query;
    $_POST                     = $post;
    $_FILES                    = $files;
    $_COOKIE                   = [];

    $reset = static function (string $class, string $prop, mixed $value): void {
        $r = new ReflectionClass($class);
        $p = $r->getProperty($prop);
        $p->setAccessible(true);
        $p->setValue(null, $value);
    };

    $reset(App\Core\Request::class, 'instance', null);
    $reset(App\Core\Auth::class, 'resolved', false);
    $reset(App\Core\Auth::class, 'user', null);

    App\Core\View::reset();

    $request = new App\Core\Request();
    App\Core\Request::setInstance($request);

    try {
        $result = $router->dispatch($request->method(), $request->path());
    } catch (App\Core\RedirectException $e) {
        return ['status' => $e->status(), 'body' => '', 'location' => $e->to(), 'error' => null];
    } catch (App\Core\HttpException $e) {
        App\Core\View::reset();
        $body = App\Core\View::render(match ($e->status()) {
            404     => 'errors.404',
            403     => 'errors.403',
            default => 'errors.generic',
        }, ['status' => $e->status(), 'message' => $e->getMessage(), 'trace' => '', 'file' => '']);
        return ['status' => $e->status(), 'body' => $body, 'location' => '', 'error' => null];
    } catch (Throwable $e) {
        return [
            'status'   => 500,
            'body'     => $e::class . ': ' . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString(),
            'location' => '',
            'error'    => $e,
        ];
    }

    if ($result instanceof App\Core\Response) {
        return [
            'status'   => $result->getStatus(),
            'body'     => $result->getBody(),
            'location' => '',
            'error'    => null,
        ];
    }

    return ['status' => 200, 'body' => (string) $result, 'location' => '', 'error' => null];
}

function loginAs(string $username, string $password): bool
{
    $_SESSION = [];
    return App\Core\Auth::attempt($username, $password, false);
}

function csrf(): string
{
    return App\Core\Csrf::token();
}

/** ساخت یک فایل تصویری واقعی برای تست بارگذاری */
function makeTempImage(string $name): array
{
    $path = sys_get_temp_dir() . '/' . $name;
    $im   = imagecreatetruecolor(120, 80);
    imagefill($im, 0, 0, imagecolorallocate($im, 30, 60, 120));
    imagepng($im, $path);
    imagedestroy($im);

    return [
        'name'     => $name,
        'type'     => 'image/png',
        'tmp_name' => $path,
        'error'    => UPLOAD_ERR_OK,
        'size'     => (int) filesize($path),
    ];
}

/* ================================================================ تست‌ها */
use App\Core\Jalali;
use App\Core\Str;
use App\Core\Validator;
use App\Models\ActivityLog;
use App\Models\Font;
use App\Models\MenuItem;
use App\Models\Brand;
use App\Models\Media;
use App\Models\Message;
use App\Models\Page;
use App\Models\Post;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Visit;
use App\Models\Work;
use App\Models\WorkImage;

echo "\n\033[1mنگاه مدیا — اجرای تست‌ها\033[0m\n";

echo "\n\033[1m۱. تبدیل تاریخ جلالی\033[0m\n";

it('تاریخ میلادی به جلالی درست تبدیل می‌شود', function (): void {
    assertSame([1403, 1, 1], Jalali::toJalali(2024, 3, 20), 'نوروز ۱۴۰۳');
    assertSame([1402, 12, 29], Jalali::toJalali(2024, 3, 19), 'پایان سال ۱۴۰۲');
    assertSame([1404, 6, 20], Jalali::toJalali(2025, 9, 11), '۲۰ شهریور ۱۴۰۴');
});

it('تاریخ جلالی به میلادی برمی‌گردد', function (): void {
    assertSame([2024, 3, 20], Jalali::toGregorian(1403, 1, 1), 'رفت و برگشت نوروز');
    assertSame([2025, 9, 11], Jalali::toGregorian(1404, 6, 20), 'رفت و برگشت شهریور');
});

it('قالب‌بندی تاریخ با ارقام فارسی', function (): void {
    $formatted = Jalali::format('2024-03-20 10:30:00', 'j F Y');
    assertContains('فروردین', $formatted, 'نام ماه');
    assertContains('۱۴۰۳', $formatted, 'سال با رقم فارسی');
});

echo "\n\033[1m۲. ابزارهای متنی\033[0m\n";

it('ارقام فارسی و انگلیسی به هم تبدیل می‌شوند', function (): void {
    assertSame('1234567890', Str::toLatinDigits('۱۲۳۴۵۶۷۸۹۰'), 'فارسی به لاتین');
    assertSame('۱۲۳۴۵۶۷۸۹۰', Str::toPersianDigits('1234567890'), 'لاتین به فارسی');
    assertSame('1234567890', Str::toLatinDigits('١٢٣٤٥٦٧٨٩٠'), 'عربی به لاتین');
});

it('ساخت اسلاگ از متن فارسی و انگلیسی', function (): void {
    assertSame('Negah-Media', Str::slug('Negah Media'), 'اسلاگ انگلیسی');
    assertSame('نگاه-مدیا', Str::slug('نگاه مدیا'), 'اسلاگ فارسی');
});

it('اسلاگ یکتا در پایگاه داده ساخته می‌شود', function (): void {
    $first = Str::uniqueSlug('brands', 'تست-یکتا');
    Brand::create(['name' => 'تست', 'slug' => $first, 'created_at' => date('Y-m-d H:i:s')]);
    $second = Str::uniqueSlug('brands', 'تست-یکتا');
    assertTrue($first !== $second, 'اسلاگ دوم متفاوت است');
    assertSame($first . '-2', $second, 'پسوند شماره‌دار');
    Brand::deleteById((int) Brand::findBySlug($first)['id']);
});

it('خلاصه‌سازی متن بدون شکستن کلمه', function (): void {
    $excerpt = Str::excerpt(str_repeat('کلمه ', 100), 20);
    assertTrue(mb_strlen($excerpt) <= 25, 'طول خلاصه محدود است');
    assertContains('…', $excerpt, 'علامت ادامه');
});

it('پاراگراف‌سازی و اندازه فایل', function (): void {
    assertContains('<p>', Str::nl2p("خط اول\n\nخط دوم"), 'تگ پاراگراف');
    assertContains('KB', Str::humanSize(2048), 'کیلوبایت');
    assertContains('MB', Str::humanSize(5 * 1024 * 1024), 'مگابایت');
});

echo "\n\033[1m۳. اعتبارسنجی\033[0m\n";

it('قواعد اجباری، ایمیل و تلفن', function (): void {
    $bad = Validator::make(
        ['name' => '', 'email' => 'bad', 'phone' => '123'],
        ['name' => 'required', 'email' => 'email', 'phone' => 'phone']
    );
    assertTrue($bad->fails(), 'داده نامعتبر رد می‌شود');
    assertTrue(array_key_exists('name', $bad->errors()), 'خطای فیلد نام ثبت شد');

    $good = Validator::make(
        ['name' => 'نگاه مدیا', 'email' => 'info@negahm.ir', 'phone' => '09121234567'],
        ['name' => 'required|minlen:2', 'email' => 'email', 'phone' => 'phone']
    );
    assertTrue($good->passes(), 'داده معتبر پذیرفته می‌شود: ' . json_encode($good->errors(), JSON_UNESCAPED_UNICODE));
});

it('قاعده یکتایی در پایگاه داده', function (): void {
    $duplicate = Validator::make(['slug' => 'رنس-تکس'], ['slug' => 'unique:brands,slug']);
    assertTrue($duplicate->fails(), 'اسلاگ تکراری رد می‌شود');

    $fresh = Validator::make(['slug' => 'slug-taze-ke-nist'], ['slug' => 'unique:brands,slug']);
    assertTrue($fresh->passes(), 'اسلاگ تازه پذیرفته می‌شود');
});

echo "\n\033[1m۴. داده‌های اولیه (seed)\033[0m\n";

it('همه جدول‌ها از schema.sql ساخته شده‌اند', function (): void {
    $tables = App\Core\DB::pdo()
        ->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")
        ->fetchAll(PDO::FETCH_COLUMN);

    assertTrue(count($tables) >= 22, 'تعداد جدول‌ها: ' . count($tables));
    foreach (['users', 'settings', 'brands', 'works', 'services', 'messages', 'posts', 'work_images'] as $table) {
        assertTrue(in_array($table, $tables, true), 'جدول ' . $table . ' ساخته شد');
    }
});

it('۳۲ برند همراه از سایت اصلی بارگذاری شده', function (): void {
    assertSame(32, Brand::count(), 'تعداد برندها');
    assertTrue(Brand::findBySlug('رنس-تکس') !== null, 'برند رنس تکس پیدا شد');
});

it('شش سرویس و هشت نمونه‌کار', function (): void {
    assertSame(6, Service::count(), 'تعداد سرویس‌ها');
    assertSame(8, Work::count(), 'تعداد نمونه‌کارها');
});

it('تنظیمات سایت از پایگاه داده خوانده می‌شود', function (): void {
    assertSame('نگاه مدیا', Setting::get('site_name'), 'نام سایت');
    assertSame('09066673416', Setting::get('contact_phone'), 'شماره تماس');
    assertContains('info@negahmedia.ir', (string) Setting::get('contact_email'), 'ایمیل تماس');
});

echo "\n\033[1m۵. صفحات عمومی سایت\033[0m\n";

it('صفحه اصلی رندر می‌شود', function (): void {
    $response = request('GET', '/');
    assertSame(200, $response['status'], 'کد وضعیت — ' . $response['body']);

    assertContains('برندت را', $response['body'], 'تیتر اصلی');
    assertContains('<em>قابلِ دیده‌شدن</em>', $response['body'], 'بخش تأکیدی تیتر');
    assertContains('همراهان نگاه', $response['body'], 'بخش برندها');
    assertContains('نمونه‌کارها', $response['body'], 'بخش نمونه‌کارها');
    assertContains('application/ld+json', $response['body'], 'داده ساختاریافته');
    assertContains('dir="rtl"', $response['body'], 'چینش راست‌به‌چپ');
    assertContains('09066673416', $response['body'], 'شماره تماس در صفحه');
});

it('صفحه برندها و صفحه اختصاصی یک برند', function (): void {
    $list = request('GET', '/brands');
    assertSame(200, $list['status'], 'فهرست برندها — ' . $list['body']);
    assertContains('رنس تکس', $list['body'], 'نام برند در فهرست');

    $detail = request('GET', '/brands/رنس-تکس');
    assertSame(200, $detail['status'], 'صفحه اختصاصی برند — ' . $detail['body']);
    assertContains('رنس تکس', $detail['body'], 'نام برند در صفحه اختصاصی');
});

it('برند منتشرنشده از دید کاربر پنهان است', function (): void {
    $id = Brand::create([
        'name' => 'برند مخفی', 'slug' => 'brand-makhfi', 'is_published' => 0,
        'has_dedicated_page' => 1, 'created_at' => date('Y-m-d H:i:s'),
    ]);
    assertSame(404, request('GET', '/brands/brand-makhfi')['status'], 'برند منتشرنشده ۴۰۴ می‌دهد');
    Brand::deleteById($id);
});

it('برند بدون صفحه اختصاصی، صفحه جدا ندارد', function (): void {
    $id = Brand::create([
        'name' => 'برند بدون صفحه', 'slug' => 'brand-bedune-safhe', 'is_published' => 1,
        'has_dedicated_page' => 0, 'created_at' => date('Y-m-d H:i:s'),
    ]);
    assertSame(404, request('GET', '/brands/brand-bedune-safhe')['status'], '۴۰۴ برای برند بدون صفحه اختصاصی');
    Brand::deleteById($id);
});

it('فهرست و جزئیات نمونه‌کار', function (): void {
    $list = request('GET', '/works');
    assertSame(200, $list['status'], 'فهرست نمونه‌کارها — ' . $list['body']);

    $work   = Work::latest(1)[0];
    $detail = request('GET', '/works/' . $work['slug']);
    assertSame(200, $detail['status'], 'صفحه نمونه‌کار — ' . $detail['body']);
    assertContains((string) $work['title'], $detail['body'], 'عنوان نمونه‌کار');
});

it('شمارنده بازدید نمونه‌کار زیاد می‌شود', function (): void {
    $work   = Work::latest(1)[0];
    $before = (int) $work['views'];
    Work::increaseViews((int) $work['id']);
    assertSame($before + 1, (int) Work::find((int) $work['id'])['views'], 'بازدید یک واحد زیاد شد');
});

it('فهرست و جزئیات سرویس', function (): void {
    $list = request('GET', '/services');
    assertSame(200, $list['status'], 'فهرست سرویس‌ها — ' . $list['body']);

    $service = Service::published()[0];
    $detail  = request('GET', '/services/' . $service['slug']);
    assertSame(200, $detail['status'], 'صفحه سرویس — ' . $detail['body']);
    assertContains((string) $service['title'], $detail['body'], 'عنوان سرویس');
});

it('صفحه‌های درباره ما، تماس و پرسش‌های متداول', function (): void {
    foreach (['/about', '/contact', '/faq'] as $path) {
        $response = request('GET', $path);
        assertSame(200, $response['status'], $path . ' → ' . $response['status'] . ' :: ' . substr($response['body'], 0, 400));
        assertTrue(strlen($response['body']) > 2000, $path . ' محتوای کافی دارد');
    }
});

it('بلاگ و صفحه یک مقاله', function (): void {
    $list = request('GET', '/blog');
    assertSame(200, $list['status'], 'فهرست بلاگ — ' . $list['body']);

    $post   = Post::latest(1)[0];
    $detail = request('GET', '/blog/' . $post['slug']);
    assertSame(200, $detail['status'], 'صفحه مقاله — ' . $detail['body']);
    assertContains((string) $post['title'], $detail['body'], 'عنوان مقاله');
});

it('جست‌وجو نتیجه برمی‌گرداند', function (): void {
    $response = request('GET', '/search', [], ['q' => 'برندینگ']);
    assertSame(200, $response['status'], 'کد وضعیت — ' . $response['body']);
    assertContains('برندینگ', $response['body'], 'عبارت جست‌وجو در نتیجه');
});

it('نقشه سایت و robots.txt', function (): void {
    $sitemap = request('GET', '/sitemap.xml');
    assertSame(200, $sitemap['status'], 'sitemap — ' . $sitemap['body']);
    assertContains('<urlset', $sitemap['body'], 'ساختار sitemap');

    $robots = request('GET', '/robots.txt');
    assertSame(200, $robots['status'], 'robots.txt');
    assertContains('User-agent', $robots['body'], 'دستور robots');
});

it('آدرس نامعتبر صفحه ۴۰۴ می‌دهد', function (): void {
    $response = request('GET', '/in-safhe-vojood-nadarad');
    assertSame(404, $response['status'], 'کد وضعیت ۴۰۴');
    assertContains('۴۰۴', $response['body'], 'صفحه خطای فارسی');
});

echo "\n\033[1m۶. فرم تماس\033[0m\n";

it('پیام تماس با داده معتبر ذخیره می‌شود', function (): void {
    $before   = Message::count();
    $response = request('POST', '/contact', [
        '_token'  => csrf(),
        'name'    => 'کاربر تست',
        'phone'   => '09121234567',
        'email'   => 'user@example.com',
        'message' => 'این یک پیام آزمایشی برای بررسی عملکرد فرم تماس است.',
        'website' => '',
    ]);
    assertSame(302, $response['status'], 'تغییر مسیر بعد از ثبت — ' . $response['body']);
    assertSame($before + 1, Message::count(), 'پیام ذخیره شد');
});

it('پیام با داده نامعتبر رد می‌شود', function (): void {
    $before = Message::count();
    request('POST', '/contact', [
        '_token' => csrf(), 'name' => '', 'phone' => '12', 'message' => 'کوتاه', 'website' => '',
    ]);
    assertSame($before, Message::count(), 'پیام نامعتبر ذخیره نشد');
});

it('ربات‌ها با فیلد دام رد می‌شوند', function (): void {
    $before = Message::count();
    request('POST', '/contact', [
        '_token'  => csrf(),
        'name'    => 'ربات',
        'phone'   => '09121234567',
        'message' => 'این یک پیام تبلیغاتی از طرف ربات‌های هرزنامه است.',
        'website' => 'http://spam.example',
    ]);
    assertSame($before, Message::count(), 'پیام ربات ذخیره نشد');
});

it('شماره پیام‌های خوانده‌نشده درست است', function (): void {
    assertTrue(Message::unreadCount() > 0, 'حداقل یک پیام خوانده‌نشده وجود دارد');
});

echo "\n\033[1m۷. امنیت\033[0m\n";

it('ورود با رمز اشتباه ناموفق است', function (): void {
    assertTrue(!loginAs('Mohusyn', 'ramz-eshtebah'), 'ورود ناموفق');
    assertTrue(!App\Core\Auth::check(), 'کاربر وارد نشده');
});

it('ورود با رمز درست موفق است', function (): void {
    assertTrue(loginAs('Mohusyn', 'Smosh1387'), 'ورود موفق با رمز seed');
    assertTrue(App\Core\Auth::check(), 'کاربر وارد شده');
    assertTrue(App\Core\Auth::isAdmin(), 'نقش کاربر مدیر است');
});

it('درخواست POST بدون توکن CSRF رد می‌شود', function (): void {
    loginAs('Mohusyn', 'Smosh1387');
    $response = request('POST', '/admin/brands', ['name' => 'نفوذ بدون توکن']);
    assertSame(419, $response['status'], 'کد وضعیت باید ۴۱۹ باشد، بود: ' . $response['status']);
    assertTrue(Brand::findBySlug('نفوذ-بدون-توکن') === null, 'برند بدون توکن ساخته نشد');
});

it('صفحه پنل بدون ورود به صفحه ورود می‌رود', function (): void {
    $_SESSION = [];
    $response = request('GET', '/admin');
    assertSame(302, $response['status'], 'کد وضعیت تغییر مسیر');
    assertContains('/admin/login', $response['location'], 'مقصد صفحه ورود');
});

it('فرم ورود رندر می‌شود', function (): void {
    $_SESSION = [];
    $response = request('GET', '/admin/login');
    assertSame(200, $response['status'], 'کد وضعیت — ' . $response['body']);
    assertContains('name="_token"', $response['body'], 'توکن CSRF در فرم');
});

it('تلاش‌های پیاوی ناموفق، ورود را موقتاً می‌بندد', function (): void {
    $_SESSION = [];
    for ($i = 0; $i < 6; $i++) {
        App\Core\Auth::attempt('Mohusyn', 'eshtebah-' . $i, false);
    }
    assertTrue(App\Core\Auth::isLocked('Mohusyn') > 0, 'حساب موقتاً قفل شد');
});

echo "\n\033[1m۸. پنل مدیریت\033[0m\n";

it('همه صفحه‌های پنل برای مدیر باز می‌شوند', function (): void {
    loginAs('Mohusyn', 'Smosh1387');

    $pages = [
        '/admin', '/admin/brands', '/admin/brands/create', '/admin/works', '/admin/works/create',
        '/admin/services', '/admin/services/create', '/admin/posts', '/admin/posts/create',
        '/admin/pages', '/admin/pages/create', '/admin/messages', '/admin/media', '/admin/sliders',
        '/admin/team', '/admin/stats', '/admin/process', '/admin/testimonials', '/admin/faqs',
        '/admin/brand-categories', '/admin/work-categories', '/admin/post-categories', '/admin/users',
        '/admin/settings', '/admin/activity', '/admin/subscribers', '/admin/profile',
    ];

    foreach ($pages as $path) {
        $response = request('GET', $path);
        assertSame(200, $response['status'], $path . ' → ' . $response['status'] . ' :: ' . substr($response['body'], 0, 400));
        assertContains('window.NEGAHM', $response['body'], $path . ' — کانفیگ جاوااسکریپت پنل');
    }
});

it('ساخت برند از پنل', function (): void {
    loginAs('Mohusyn', 'Smosh1387');
    $response = request('POST', '/admin/brands', [
        '_token'             => csrf(),
        'name'               => 'برند آزمایشی',
        'name_en'            => 'Test Brand',
        'slug'               => 'brand-azmayeshi',
        'category_id'        => 1,
        'excerpt'            => 'خلاصه برند آزمایشی',
        'body'               => 'توضیح کامل برند آزمایشی برای تست.',
        'is_published'       => '1',
        'is_featured'        => '1',
        'has_dedicated_page' => '1',
        'socials'            => ['instagram' => 'negahmedia', 'telegram' => 'negahm'],
    ]);
    assertSame(302, $response['status'], 'تغییر مسیر بعد از ساخت — ' . $response['body']);

    $brand = Brand::findBySlug('brand-azmayeshi');
    assertTrue($brand !== null, 'برند ساخته شد');
    assertSame(1, (int) $brand['is_published'], 'وضعیت انتشار');
    assertSame('negahmedia', Brand::decodeSocials($brand['socials'])['instagram'] ?? '', 'شبکه اجتماعی ذخیره شد');

    $page = request('GET', '/brands/brand-azmayeshi');
    assertSame(200, $page['status'], 'صفحه اختصاصی برند جدید باز می‌شود — ' . $page['body']);
});

it('ویرایش و تغییر وضعیت برند', function (): void {
    loginAs('Mohusyn', 'Smosh1387');
    $id = (int) Brand::findBySlug('brand-azmayeshi')['id'];

    request('POST', '/admin/brands/' . $id, [
        '_token' => csrf(), 'name' => 'برند ویرایش‌شده', 'slug' => 'brand-azmayeshi',
        'excerpt' => 'خلاصه جدید', 'is_published' => '1',
    ]);
    assertSame('برند ویرایش‌شده', (string) Brand::find($id)['name'], 'نام به‌روز شد');

    request('POST', '/admin/brands/' . $id . '/toggle/is_published', ['_token' => csrf()]);
    assertSame(0, (int) Brand::find($id)['is_published'], 'وضعیت انتشار تغییر کرد');

    request('POST', '/admin/brands/' . $id . '/toggle/is_published', ['_token' => csrf()]);
    assertSame(1, (int) Brand::find($id)['is_published'], 'وضعیت انتشار برگشت');
});

it('بارگذاری تصویر و ثبت آن در کتابخانه رسانه', function (): void {
    loginAs('Mohusyn', 'Smosh1387');
    $before = Media::count();

    $response = request('POST', '/admin/brands', [
        '_token'       => csrf(),
        'name'         => 'برند با لوگو',
        'slug'         => 'brand-ba-logo',
        'is_published' => '1',
    ], [], ['logo' => makeTempImage('logo-test.png')]);

    assertSame(302, $response['status'], 'تغییر مسیر بعد از ساخت — ' . $response['body']);

    $brand = Brand::findBySlug('brand-ba-logo');
    assertTrue($brand !== null, 'برند ساخته شد');
    assertTrue(!empty($brand['logo']), 'مسیر لوگو ذخیره شد');
    assertSame($before + 1, Media::count(), 'فایل در کتابخانه رسانه ثبت شد');
    assertTrue(is_file((string) App\Core\Config::get('paths.root') . '/public' . $brand['logo']), 'فایل روی دیسک وجود دارد');
});

it('ساخت نمونه‌کار با گالری تصاویر', function (): void {
    loginAs('Mohusyn', 'Smosh1387');
    $brandId = (int) Brand::findBySlug('brand-ba-logo')['id'];

    $files = ['gallery' => [
        'name'     => ['a.png', 'b.png'],
        'type'     => ['image/png', 'image/png'],
        'tmp_name' => [makeTempImage('g1.png')['tmp_name'], makeTempImage('g2.png')['tmp_name']],
        'error'    => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
        'size'     => [1024, 1024],
    ]];

    $response = request('POST', '/admin/works', [
        '_token'       => csrf(),
        'title'        => 'پروژه آزمایشی نگاه',
        'slug'         => 'proje-azmayeshi',
        'brand_id'     => $brandId,
        'category_id'  => 1,
        'excerpt'      => 'خلاصه پروژه آزمایشی',
        'body'         => 'شرح کامل پروژه آزمایشی.',
        'challenge'    => 'چالش پروژه',
        'solution'     => 'راه‌حل ما',
        'result'       => 'نتیجه نهایی',
        'year'         => '۱۴۰۳',
        'is_published' => '1',
        'gallery_alt'  => ['تصویر یک', 'تصویر دو'],
    ], [], $files);

    assertSame(302, $response['status'], 'تغییر مسیر بعد از ساخت — ' . $response['body']);

    $work = Work::findBySlug('proje-azmayeshi');
    assertTrue($work !== null, 'نمونه‌کار ساخته شد');

    $images = WorkImage::forWork((int) $work['id']);
    assertSame(2, count($images), 'دو تصویر گالری ذخیره شد');
    assertSame('تصویر یک', (string) $images[0]['alt'], 'متن جایگزین تصویر اول');

    $detail = request('GET', '/works/proje-azmayeshi');
    assertSame(200, $detail['status'], 'صفحه نمونه‌کار جدید — ' . $detail['body']);
    assertContains('پروژه آزمایشی نگاه', $detail['body'], 'عنوان در صفحه');
});

it('حذف نمونه‌کار، تصاویر گالری را هم پاک می‌کند', function (): void {
    loginAs('Mohusyn', 'Smosh1387');
    $id = (int) Work::findBySlug('proje-azmayeshi')['id'];
    assertTrue(count(WorkImage::forWork($id)) === 2, 'گالری پیش از حذف موجود است');

    request('POST', '/admin/works/' . $id . '/delete', ['_token' => csrf()]);
    assertTrue(Work::find($id) === null, 'نمونه‌کار حذف شد');
    assertSame(0, count(WorkImage::forWork($id)), 'تصاویر گالری هم حذف شدند');
});

it('حذف برند از پنل', function (): void {
    loginAs('Mohusyn', 'Smosh1387');
    $id = (int) Brand::findBySlug('brand-azmayeshi')['id'];
    request('POST', '/admin/brands/' . $id . '/delete', ['_token' => csrf()]);
    assertTrue(Brand::find($id) === null, 'برند حذف شد');
});

it('ذخیره تنظیمات یک گروه، بقیه گروه‌ها را پاک نمی‌کند', function (): void {
    loginAs('Mohusyn', 'Smosh1387');
    $phoneBefore = Setting::get('contact_phone');
    $heroBefore  = Setting::get('hero_title');

    $response = request('POST', '/admin/settings', [
        '_token'       => csrf(),
        'group'        => 'general',
        'site_name'    => 'نگاه مدیا — به‌روز شد',
        'site_tagline' => 'آژانس خلاق و تبلیغاتی',
        'site_url'     => 'https://negahm.ir',
        'footer_about' => 'متن درباره ما در پابرگ',
        'footer_note'  => '© نگاه مدیا',
    ]);
    assertSame(302, $response['status'], 'تغییر مسیر بعد از ذخیره — ' . $response['body']);

    assertSame('نگاه مدیا — به‌روز شد', Setting::get('site_name'), 'تنظیم گروه general ذخیره شد');
    assertSame($phoneBefore, Setting::get('contact_phone'), 'تنظیمات گروه contact پاک نشد');
    assertSame($heroBefore, Setting::get('hero_title'), 'تنظیمات گروه home پاک نشد');

    Setting::put('site_name', 'نگاه مدیا', 'string', 'general', 'نام سایت');
});

it('تغییر عنوان بخش معرفی در پنل، روی صفحه اصلی اثر می‌گذارد', function (): void {
    loginAs('Mohusyn', 'Smosh1387');
    $original = Setting::get('hero_title');

    request('POST', '/admin/settings', [
        '_token'     => csrf(),
        'group'      => 'home',
        'hero_title' => 'عنوان آزمایشی از پنل',
        'hero_lead'  => 'متن معرفی آزمایشی',
        'hero_kicker' => 'جمله بالایی آزمایشی',
        'home_quote' => 'نقل‌قول آزمایشی',
        'about_short' => 'متن کوتاه آزمایشی',
    ]);
    assertSame('عنوان آزمایشی از پنل', Setting::get('hero_title'), 'تنظیم ذخیره شد');

    // کش درون‌درخواستی setting() باید در درخواست بعدی تازه شود
    $response = request('GET', '/');
    assertContains('عنوان آزمایشی از پنل', $response['body'], 'عنوان جدید در صفحه اصلی دیده می‌شود');

    Setting::put('hero_title', $original, 'string', 'home', 'عنوان بخش معرفی');
});

it('عنوان سئوی هر صفحه از تنظیمات همان صفحه می‌آید', function (): void {
    $response = request('GET', '/brands');
    assertContains('برندهایی که به ما اعتماد کردند', $response['body'], 'عنوان سئوی صفحه برندها');

    $response = request('GET', '/works');
    assertContains('نمونه‌کارها', $response['body'], 'عنوان سئوی صفحه نمونه‌کارها');
});

it('تنظیمات سئوی صفحه‌ها در پنل قابل ویرایش است', function (): void {
    loginAs('Mohusyn', 'Smosh1387');
    $response = request('GET', '/admin/settings', [], ['group' => 'pages']);
    assertSame(200, $response['status'], 'کد وضعیت — ' . $response['body']);
    assertContains('brands_seo_title', $response['body'], 'فیلد سئوی صفحه برندها در پنل');
    assertContains('سئوی صفحه‌ها', $response['body'], 'برچسب گروه');
});

it('رویدادهای فعالیت کاربر ثبت می‌شوند', function (): void {
    $count = ActivityLog::count();
    assertTrue($count > 0, 'رویدادها ثبت شده‌اند (تعداد: ' . $count . ')');
});

it('پنل پیام‌ها، پیام ثبت‌شده را نشان می‌دهد', function (): void {
    loginAs('Mohusyn', 'Smosh1387');
    $response = request('GET', '/admin/messages');
    assertSame(200, $response['status'], 'کد وضعیت — ' . $response['body']);
    assertContains('کاربر تست', $response['body'], 'نام فرستنده در فهرست پیام‌ها');
});

/* ------------------------------------------------- منو، فونت و آمار */
echo "\n\033[1m۱۱. مدیریت منو، فونت و آمار بازدید\033[0m\n";

it('منوی سایت از پنل مدیریت می‌آید', function (): void {
    $response = request('GET', '/');
    assertSame(200, $response['status'], 'کد وضعیت — ' . $response['body']);
    assertContains('desktop-nav', $response['body'], 'منوی دسکتاپ رندر شده');
    assertContains('mobile-nav', $response['body'], 'منوی موبایل رندر شده');
    assertContains('نمونه‌کارها', $response['body'], 'آیتم seed در منو هست');
});

it('آیتم جدید از پنل به منو اضافه و از سایت حذف می‌شود', function (): void {
    loginAs('Mohusyn', 'Smosh1387');

    $response = request('POST', '/admin/menu', [
        '_token'       => csrf(),
        'type'         => 'custom',
        'url'          => 'https://example.com',
        'title'        => 'لینک آزمایشی منو',
        'position'     => 'header',
        'parent_id'    => '0',
        'show_desktop' => '1',
        'show_mobile'  => '1',
        'is_active'    => '1',
        'sort_order'   => '99',
    ]);
    assertSame(302, $response['status'], 'تغییر مسیر بعد از افزودن — ' . $response['body']);

    $page = request('GET', '/');
    assertContains('لینک آزمایشی منو', $page['body'], 'آیتم جدید در سایت دیده می‌شود');
    assertContains('https://example.com', $page['body'], 'آدرس دلخواه درست درج شده');

    $item = null;
    foreach (MenuItem::all() as $row) {
        if ((string) $row['title'] === 'لینک آزمایشی منو') {
            $item = $row;
        }
    }
    assertTrue($item !== null, 'آیتم در پایگاه داده ثبت شد');

    request('POST', '/admin/menu/' . (int) $item['id'] . '/delete', ['_token' => csrf()]);
    $after = request('GET', '/');
    assertTrue(!str_contains($after['body'], 'لینک آزمایشی منو'), 'پس از حذف، از سایت gone است');
});

it('منوی موبایل و دسکتاپ جدا کنترل می‌شوند', function (): void {
    $desktop = MenuItem::tree('header', 'desktop');
    $mobile  = MenuItem::tree('header', 'mobile');

    assertTrue($desktop !== [], 'منوی دسکتاپ خالی نیست');
    assertTrue($mobile !== [], 'منوی موبایل خالی نیست');

    $labels = static function (array $items): array {
        return array_map(static fn ($i) => (string) $i['label'], $items);
    };

    // در seed، «درباره ما» فقط برای دسکتاپ است
    assertTrue(in_array('درباره ما', $labels($desktop), true), '«درباره ما» در دسکتاپ هست');
    assertTrue(!in_array('درباره ما', $labels($mobile), true), '«درباره ما» در موبایل نیست');
});

it('اگر منو خالی باشد، منوی پیش‌فرض جایگزین می‌شود', function (): void {
    $fallback = MenuItem::defaultTree();
    assertTrue($fallback !== [], 'منوی پیش‌فرض خالی نیست');
    assertTrue(in_array('/', array_column($fallback, 'href'), true), 'خانه در منوی پیش‌فرض هست');
});

it('آیتم وصل‌شده به یک رکورد، عنوان و آدرسش را از همان رکورد می‌گیرد', function (): void {
    loginAs('Mohusyn', 'Smosh1387');

    // ستون عنوان در جدول‌ها یکی نیست: pages عنوان دارد و brands نام.
    // هر دو باید بدون خطا عنوان درست بدهند.
    $page = Page::all()[0] ?? null;
    assertTrue($page !== null, 'یک صفحه برای آزمایش وجود دارد');
    $label = MenuItem::resolveTitle(['title' => '', 'type' => 'page', 'reference_id' => (int) $page['id'], 'url' => '']);
    assertSame((string) $page['title'], $label, 'عنوان از جدول pages خوانده شد');
    assertSame('/p/' . $page['slug'], MenuItem::resolveUrl(['type' => 'page', 'reference_id' => (int) $page['id'], 'url' => '']), 'آدرس صفحه');

    $brand = Brand::all()[0] ?? null;
    assertTrue($brand !== null, 'یک برند برای آزمایش وجود دارد');
    assertSame((string) $brand['name'], MenuItem::resolveTitle(['title' => '', 'type' => 'brand', 'reference_id' => (int) $brand['id'], 'url' => '']), 'عنوان از جدول brands خوانده شد');

    // رکورد حذف‌شده نباید خطا بدهد؛ به آدرس ذخیره‌شده برمی‌گردیم
    assertSame('/fallback', MenuItem::resolveUrl(['type' => 'page', 'reference_id' => 999999, 'url' => '/fallback']), 'رکورد حذف‌شده به آدرس ذخیره‌شده برمی‌گردد');
});

it('فونت فعال، @font-face برای کل سایت تولید می‌کند', function (): void {
    $id = Font::create([
        'name'       => 'فونت تست',
        'slug'       => 'font-test',
        'file'       => '/uploads/fonts/test.woff2',
        'format'     => 'woff2',
        'weight_min' => 100,
        'weight_max' => 900,
        'is_active'  => 1,
        'created_at' => Font::now(),
    ]);
    Font::activate($id);

    $css = Font::faceCss();
    assertContains('@font-face', $css, 'قاعده @font-face ساخته شد');
    assertContains('font-weight:100 900', $css, 'بازه وزن فونت متغیر');
    assertContains('/uploads/fonts/test.woff2', $css, 'مسیر فایل فونت');

    $response = request('GET', '/');
    assertContains('@font-face', $response['body'], 'فونت در صفحه اصلی تزریق شده');

    Font::deleteById($id);
    assertSame('', Font::faceCss(), 'بدون فونت فعال، CSS خالی است');
});

it('بازدید صفحه ثبت و در پنل نمایش داده می‌شود', function (): void {
    App\Models\Visit::track('/brands');
    App\Models\Visit::track('/services');

    $stats = Visit::pageStats(30, 20, 1);
    assertTrue((int) $stats['total'] >= 2, 'حداقل دو صفحه ثبت شده است');

    $paths = array_column($stats['data'], 'path');
    assertTrue(in_array('/brands', $paths, true), 'مسیر /brands ثبت شده');
    assertContains('برندها', (string) Visit::titleFor('/brands'), 'عنوان صفحه از روی مسیر حدس زده شد');

    loginAs('Mohusyn', 'Smosh1387');
    $response = request('GET', '/admin/analytics');
    assertSame(200, $response['status'], 'صفحه آمار باز می‌شود — ' . $response['body']);
    assertContains('بازدید به تفکیک صفحه', $response['body'], 'جدول آمار در پنل هست');
    assertContains('/brands', $response['body'], 'مسیر ثبت‌شده در جدول دیده می‌شود');
});

/* ----------------------------------------------------------- جمع‌بندی */
echo "\n" . str_repeat('─', 62) . "\n";
if ($failed === 0) {
    echo "\033[32m\033[1mهمه {$passed} تست موفق بود.\033[0m\n";
} else {
    echo "\033[31m\033[1m{$failed} تست ناموفق\033[0m از " . ($passed + $failed) . " تست\n\n";
    foreach ($failures as $failure) {
        echo '  • ' . $failure . "\n";
    }
}

@unlink($root . '/.env');
@unlink($dbFile);

exit($failed === 0 ? 0 : 1);
