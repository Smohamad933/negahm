<?php
use App\Core\View;
View::extend('layouts.front');
$activeCategory = $activeCategory ?? null;
?>
<?php View::start('content'); ?>

<?= partial('partials.page-hero', [
    'title'  => $activeCategory ? $activeCategory['title'] : 'کارهایی که حرف می‌زنند.',
    'lead'   => $activeCategory && !empty($activeCategory['description'])
        ? $activeCategory['description']
        : 'مجموعه‌ای از پروژه‌های هویت بصری، کمپین، تولید محتوا و طراحی وب که برای برندهای همراه اجرا کرده‌ایم.',
    'crumbs' => [
        ['label' => 'خانه', 'url' => url('/')],
        ['label' => 'نمونه‌کارها', 'url' => url('/works')],
    ],
]) ?>

<section class="listing">
  <div class="container">

    <div class="filter-bar">
      <form class="filter-search" action="<?= e(url('/works')) ?>" method="get" role="search">
        <label class="sr-only" for="work-q">جست‌وجوی پروژه</label>
        <input id="work-q" type="search" name="q" value="<?= e($q) ?>" placeholder="جست‌وجوی پروژه یا برند…">
        <button type="submit">جست‌وجو</button>
      </form>

      <?php if (!empty($categories)): ?>
      <ul class="chip-row" role="list">
        <li><a class="chip <?= (int) $activeCat === 0 ? 'is-active' : '' ?>" href="<?= e(url('/works')) ?>">همه</a></li>
        <?php foreach ($categories as $category): ?>
        <li>
          <a class="chip <?= (int) $activeCat === (int) $category['id'] ? 'is-active' : '' ?>"
             href="<?= e(url('/works?category=' . $category['id'])) ?>">
            <?= e($category['title']) ?>
            <small><?= e(jdigits($category['works_count'] ?? 0)) ?></small>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>

    <p class="result-count"><?= e(jdigits($pager['total'])) ?> پروژه یافت شد</p>

    <?php if ($pager['data'] === []): ?>
      <div class="empty-state">
        <p>پروژه‌ای با این مشخصات پیدا نشد.</p>
        <a class="btn btn-ghost" href="<?= e(url('/works')) ?>">نمایش همه پروژه‌ها</a>
      </div>
    <?php else: ?>
      <div class="work-grid work-grid-list">
        <?php foreach ($pager['data'] as $index => $work): ?>
        <a class="work-card <?= $index === 0 ? 'work-card-wide' : '' ?>" href="<?= e(url('/works/' . $work['slug'])) ?>" data-reveal>
          <span class="work-media">
            <?php if (!empty($work['cover'])): ?>
              <img src="<?= e(upload_url($work['cover'])) ?>" alt="<?= e($work['title']) ?>" loading="lazy">
            <?php else: ?>
              <span class="work-media-fallback" aria-hidden="true"><?= e(mb_substr($work['title'], 0, 1)) ?></span>
            <?php endif; ?>
          </span>
          <span class="work-body">
            <?php if (!empty($work['category_label']) || !empty($work['category_title'])): ?>
            <em class="work-tag"><?= e($work['category_label'] ?: $work['category_title']) ?></em>
            <?php endif; ?>
            <strong><?= e($work['title']) ?></strong>
            <?php if (!empty($work['excerpt'])): ?>
            <small><?= e(excerpt((string) $work['excerpt'], 90)) ?></small>
            <?php endif; ?>
            <?php if (!empty($work['brand_name'])): ?>
            <small class="work-brand"><?= e($work['brand_name']) ?></small>
            <?php endif; ?>
          </span>
          <span class="work-arrow" aria-hidden="true">↗</span>
        </a>
        <?php endforeach; ?>
      </div>

      <?= pagination_links($pager, '/works') ?>
    <?php endif; ?>

  </div>
</section>

<?php View::stop(); ?>
