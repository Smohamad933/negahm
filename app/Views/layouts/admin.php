<?php
/** @var string $pageTitle */
use App\Core\View;
use App\Core\Auth;
use App\Models\Message;

$user = Auth::user() ?? [];
$unread = $unreadCount ?? Message::unreadCount();
$current = $_SERVER['REQUEST_URI'] ?? '';
$inPanel = str_contains($current, '/admin');

$menu = [
    ['label' => 'داشبورد', 'icon' => '◈', 'url' => '/admin', 'match' => '#^/admin/?($|\?)#'],
    ['label' => 'برندها', 'icon' => '◆', 'url' => '/admin/brands', 'match' => '#^/admin/brands#'],
    ['label' => 'دسته‌بندی برندها', 'icon' => '◇', 'url' => '/admin/brand-categories', 'match' => '#^/admin/brand-categories#'],
    ['label' => 'نمونه‌کارها', 'icon' => '▣', 'url' => '/admin/works', 'match' => '#^/admin/works#'],
    ['label' => 'دسته‌بندی نمونه‌کار', 'icon' => '▤', 'url' => '/admin/work-categories', 'match' => '#^/admin/work-categories#'],
    ['label' => 'خدمات', 'icon' => '✦', 'url' => '/admin/services', 'match' => '#^/admin/services#'],
    ['label' => 'مقالات', 'icon' => '✎', 'url' => '/admin/posts', 'match' => '#^/admin/posts#'],
    ['label' => 'دسته‌بندی مقالات', 'icon' => '❖', 'url' => '/admin/post-categories', 'match' => '#^/admin/post-categories#'],
    ['label' => 'صفحات', 'icon' => '▭', 'url' => '/admin/pages', 'match' => '#^/admin/pages#'],
    ['label' => 'پیام‌ها', 'icon' => '✉', 'url' => '/admin/messages', 'match' => '#^/admin/messages#', 'badge' => $unread],
    ['label' => 'رسانه‌ها', 'icon' => '🖼', 'url' => '/admin/media', 'match' => '#^/admin/media#'],
    ['label' => 'اعضای تیم', 'icon' => '☺', 'url' => '/admin/team', 'match' => '#^/admin/team#'],
    ['label' => 'نظرات مشتریان', 'icon' => '❝', 'url' => '/admin/testimonials', 'match' => '#^/admin/testimonials#'],
    ['label' => 'شمارنده‌های آماری', 'icon' => '⌁', 'url' => '/admin/stats', 'match' => '#^/admin/stats#'],
    ['label' => 'مراحل همکاری', 'icon' => '⇄', 'url' => '/admin/process', 'match' => '#^/admin/process#'],
    ['label' => 'اسلایدر', 'icon' => '▤', 'url' => '/admin/sliders', 'match' => '#^/admin/sliders#'],
    ['label' => 'سوالات متداول', 'icon' => '؟', 'url' => '/admin/faqs', 'match' => '#^/admin/faqs#'],
    ['label' => 'خبرنامه', 'icon' => '✧', 'url' => '/admin/subscribers', 'match' => '#^/admin/subscribers#'],
    ['label' => 'آمار بازدید', 'icon' => '📈', 'url' => '/admin/analytics', 'match' => '#^/admin/analytics#'],
    ['label' => 'گزارش فعالیت‌ها', 'icon' => '≡', 'url' => '/admin/activity', 'match' => '#^/admin/activity#'],
];

// این بخش‌ها میان‌افزار «admin» دارند؛ پس لینکشان هم فقط به مدیر نشان داده
// می‌شود تا ویرایشگر با لینک بن‌بست (۴۰۳) روبه‌رو نشود.
if (Auth::isAdmin()) {
    $menu[] = ['label' => 'مدیریت منو', 'icon' => '☰', 'url' => '/admin/menu', 'match' => '#^/admin/menu#'];
    $menu[] = ['label' => 'فونت سایت', 'icon' => 'A', 'url' => '/admin/fonts', 'match' => '#^/admin/fonts#'];
    $menu[] = ['label' => 'کاربران', 'icon' => '⚇', 'url' => '/admin/users', 'match' => '#^/admin/users#'];
    $menu[] = ['label' => 'تنظیمات', 'icon' => '⚙', 'url' => '/admin/settings', 'match' => '#^/admin/settings#'];
}
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? 'پنل مدیریت') ?></title>
<meta name="robots" content="noindex,nofollow">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
<?php if (!empty($fontCss)): ?>
<style><?= $fontCss ?></style>
<?php endif; ?>
<script>window.NEGAHM = { csrf: "<?= e(csrf_token()) ?>", base: "<?= e(url('/')) ?>" };</script>
</head>
<body class="admin-body">

<div class="admin-shell">

  <aside class="admin-sidebar" data-sidebar>
    <div class="sidebar-head">
      <a class="admin-brand" href="<?= e(url('/admin')) ?>">
        <span class="brand-mark" aria-hidden="true">N</span>
        <span><strong><?= e($adminName ?? 'نگاه مدیا') ?></strong><small>پنل مدیریت</small></span>
      </a>
      <button class="sidebar-close" type="button" data-sidebar-close aria-label="بستن منو">×</button>
    </div>

    <nav class="sidebar-nav" aria-label="منوی پنل">
      <ul>
        <?php foreach ($menu as $item): ?>
        <?php $active = preg_match($item['match'], $current) === 1; ?>
        <li>
          <a href="<?= e(url($item['url'])) ?>" class="<?= $active ? 'is-active' : '' ?>" <?= $active ? 'aria-current="page"' : '' ?>>
            <span class="menu-icon" aria-hidden="true"><?= e($item['icon']) ?></span>
            <span class="menu-label"><?= e($item['label']) ?></span>
            <?php if (!empty($item['badge'])): ?>
            <span class="menu-badge"><?= e(jdigits($item['badge'])) ?></span>
            <?php endif; ?>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
    </nav>

    <div class="sidebar-foot">
      <a href="<?= e(url('/')) ?>" target="_blank" rel="noopener">مشاهده سایت ↗</a>
      <form action="<?= e(url('/admin/logout')) ?>" method="post">
        <?= csrf_field() ?>
        <button type="submit" class="logout-btn">خروج از حساب</button>
      </form>
    </div>
  </aside>

  <div class="admin-main">

    <header class="admin-topbar">
      <button class="sidebar-toggle" type="button" data-sidebar-toggle aria-label="باز کردن منو">☰</button>

      <h1 class="topbar-title"><?= e($pageTitle ?? 'پنل مدیریت') ?></h1>

      <div class="topbar-actions">
        <a class="topbar-link" href="<?= e(url('/')) ?>" target="_blank" rel="noopener">سایت ↗</a>
        <div class="topbar-user">
          <span class="topbar-avatar" aria-hidden="true"><?= e(mb_substr((string) ($user['name'] ?? 'U'), 0, 1)) ?></span>
          <span class="topbar-user-info">
            <strong><?= e($user['name'] ?? '') ?></strong>
            <small><?= e($user['role'] === 'admin' ? 'مدیر' : 'ویرایشگر') ?></small>
          </span>
          <a class="topbar-link" href="<?= e(url('/admin/profile')) ?>">پروفایل</a>
        </div>
      </div>
    </header>

    <main class="admin-content">
      <div class="flash-zone"><?= flash_render() ?></div>
      <?= View::section('content') ?>
    </main>

    <footer class="admin-footer">
      <span>پنل مدیریت <?= e($adminName ?? 'نگاه مدیا') ?></span>
      <span><?= e(jdate(date('Y-m-d H:i:s'), 'l، j F Y', true)) ?></span>
    </footer>

  </div>
</div>

<script src="<?= e(asset('js/admin.js')) ?>" defer></script>
</body>
</html>
