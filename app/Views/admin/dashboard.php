<?php
use App\Core\View;
View::extend('layouts.admin'); ?>
<?php View::start('content'); ?>

<section class="stat-cards">
  <?php
  $cards = [
      ['label' => 'برندها', 'value' => $stats['brands'], 'hint' => jdigits($stats['brandsPub']) . ' منتشر شده', 'url' => '/admin/brands', 'icon' => '◆'],
      ['label' => 'نمونه‌کارها', 'value' => $stats['works'], 'hint' => jdigits($stats['worksPub']) . ' منتشر شده', 'url' => '/admin/works', 'icon' => '▣'],
      ['label' => 'خدمات', 'value' => $stats['services'], 'hint' => 'خدمات فعال', 'url' => '/admin/services', 'icon' => '✦'],
      ['label' => 'مقالات', 'value' => $stats['posts'], 'hint' => 'نوشته‌های بلاگ', 'url' => '/admin/posts', 'icon' => '✎'],
      ['label' => 'پیام‌ها', 'value' => $stats['messages'], 'hint' => jdigits($stats['unread']) . ' خوانده‌نشده', 'url' => '/admin/messages', 'icon' => '✉'],
      ['label' => 'رسانه‌ها', 'value' => $stats['media'], 'hint' => \App\Core\Str::humanSize((int) $stats['mediaSize']), 'url' => '/admin/media', 'icon' => '🖼'],
      ['label' => 'خبرنامه', 'value' => $stats['subscribers'], 'hint' => 'عضو فعال', 'url' => '/admin/subscribers', 'icon' => '✧'],
      ['label' => 'بازدید امروز', 'value' => $stats['visitsToday'], 'hint' => 'مجموع: ' . jdigits($stats['visitsTotal']), 'url' => '/admin/activity', 'icon' => '◈'],
  ];
  ?>
  <?php foreach ($cards as $card): ?>
  <a class="stat-card" href="<?= e(url($card['url'])) ?>">
    <span class="stat-card-icon" aria-hidden="true"><?= e($card['icon']) ?></span>
    <span class="stat-card-body">
      <small><?= e($card['label']) ?></small>
      <strong><?= e(jdigits($card['value'])) ?></strong>
      <em><?= e($card['hint']) ?></em>
    </span>
  </a>
  <?php endforeach; ?>
</section>

<div class="dash-grid">

  <section class="panel panel-wide">
    <header class="panel-head">
      <h2>روند بازدید ۱۴ روز اخیر</h2>
    </header>
    <div class="panel-body">
      <?php if ($visits === []): ?>
        <p class="panel-empty">داده‌ای برای نمایش وجود ندارد.</p>
      <?php else: ?>
      <div class="chart" role="img" aria-label="نمودار بازدید ۱۴ روز اخیر">
        <?php foreach ($visits as $date => $count): ?>
        <div class="chart-col">
          <span class="chart-bar" style="height: <?= e((string) max(3, (int) round(((int) $count / max(1, $maxVisit)) * 100))) ?>%"></span>
          <small title="<?= e(jdate($date . ' 00:00:00', 'j F')) ?>"><?= e(jdigits((int) $count)) ?></small>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php if (!empty($topPaths)): ?>
      <table class="table table-compact">
        <caption>پربازدیدترین صفحات (۳۰ روز اخیر)</caption>
        <thead><tr><th>مسیر</th><th>بازدید</th></tr></thead>
        <tbody>
          <?php foreach ($topPaths as $path): ?>
          <tr><td><code><?= e($path['path']) ?></code></td><td><?= e(jdigits($path['visits'])) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </section>

  <section class="panel">
    <header class="panel-head">
      <h2>پیام‌های خوانده‌نشده</h2>
      <a class="panel-link" href="<?= e(url('/admin/messages')) ?>">همه ←</a>
    </header>
    <div class="panel-body">
      <?php if (empty($recentMessages)): ?>
        <p class="panel-empty">پیام خوانده‌نشده‌ای ندارید.</p>
      <?php else: ?>
      <ul class="mini-list">
        <?php foreach ($recentMessages as $message): ?>
        <li>
          <a href="<?= e(url('/admin/messages/' . $message['id'])) ?>">
            <strong><?= e($message['name']) ?></strong>
            <small><?= e(excerpt((string) $message['body'], 60)) ?></small>
            <time><?= e(\App\Core\Jalali::ago($message['created_at'])) ?></time>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
  </section>

  <section class="panel">
    <header class="panel-head">
      <h2>آخرین نمونه‌کارها</h2>
      <a class="panel-link" href="<?= e(url('/admin/works')) ?>">همه ←</a>
    </header>
    <div class="panel-body">
      <?php if (empty($recentWorks)): ?>
        <p class="panel-empty">هنوز نمونه‌کاری ثبت نشده است.</p>
      <?php else: ?>
      <ul class="mini-list">
        <?php foreach ($recentWorks as $work): ?>
        <li>
          <a href="<?= e(url('/admin/works/' . $work['id'] . '/edit')) ?>">
            <strong><?= e($work['title']) ?></strong>
            <small><?= e($work['brand_name'] ?? '—') ?></small>
            <span class="status-dot <?= (int) $work['is_published'] === 1 ? 'is-on' : 'is-off' ?>"></span>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
  </section>

  <section class="panel">
    <header class="panel-head">
      <h2>آخرین برندها</h2>
      <a class="panel-link" href="<?= e(url('/admin/brands')) ?>">همه ←</a>
    </header>
    <div class="panel-body">
      <?php if (empty($recentBrands)): ?>
        <p class="panel-empty">هنوز برندی ثبت نشده است.</p>
      <?php else: ?>
      <ul class="mini-list">
        <?php foreach ($recentBrands as $brand): ?>
        <li>
          <a href="<?= e(url('/admin/brands/' . $brand['id'] . '/edit')) ?>">
            <strong><?= e($brand['name']) ?></strong>
            <small><?= e(jdigits($brand['works_count'] ?? 0)) ?> پروژه</small>
            <span class="status-dot <?= (int) $brand['is_published'] === 1 ? 'is-on' : 'is-off' ?>"></span>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
  </section>

  <section class="panel panel-wide">
    <header class="panel-head">
      <h2>فعالیت‌های اخیر</h2>
      <a class="panel-link" href="<?= e(url('/admin/activity')) ?>">گزارش کامل ←</a>
    </header>
    <div class="panel-body">
      <?php if (empty($activities)): ?>
        <p class="panel-empty">فعالیتی ثبت نشده است.</p>
      <?php else: ?>
      <ul class="activity-list">
        <?php foreach ($activities as $activity): ?>
        <li>
          <span class="activity-dot" aria-hidden="true"></span>
          <span class="activity-body">
            <strong><?= e($activity['description'] ?: $activity['action']) ?></strong>
            <small><?= e($activity['user_name'] ?? 'سیستم') ?> · <?= e(\App\Core\Jalali::ago($activity['created_at'])) ?></small>
          </span>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
  </section>

</div>

<?php View::stop(); ?>
