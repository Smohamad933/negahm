<?php
use App\Core\View;
View::extend('layouts.front');
?>
<?php View::start('content'); ?>

<?= partial('partials.page-hero', [
    'title'  => 'برندهایی که به ما اعتماد کردند.',
    'lead'   => 'همکاری برای ما فقط تحویل یک پروژه نیست؛ ساختن یک رابطه‌ی حرفه‌ای و ماندگار است.',
    'crumbs' => [
        ['label' => 'خانه', 'url' => url('/')],
        ['label' => 'برندها', 'url' => ''],
    ],
]) ?>

<section class="listing">
  <div class="container">

    <div class="filter-bar">
      <form class="filter-search" action="<?= e(url('/brands')) ?>" method="get" role="search">
        <label class="sr-only" for="brand-q">جست‌وجوی برند</label>
        <input id="brand-q" type="search" name="q" value="<?= e($q) ?>" placeholder="جست‌وجوی برند یا صنعت…">
        <button type="submit">جست‌وجو</button>
      </form>

      <?php if (!empty($categories)): ?>
      <ul class="chip-row" role="list">
        <li>
          <a class="chip <?= (int) $activeCat === 0 ? 'is-active' : '' ?>" href="<?= e(url('/brands')) ?>">همه</a>
        </li>
        <?php foreach ($categories as $category): ?>
        <li>
          <a class="chip <?= (int) $activeCat === (int) $category['id'] ? 'is-active' : '' ?>"
             href="<?= e(url('/brands?category=' . $category['id'])) ?>">
            <?= e($category['title']) ?>
            <small><?= e(jdigits($category['brands_count'] ?? 0)) ?></small>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>

    <p class="result-count"><?= e(jdigits($pager['total'])) ?> برند یافت شد</p>

    <?php if ($pager['data'] === []): ?>
      <div class="empty-state">
        <p>برندی با این مشخصات پیدا نشد.</p>
        <a class="btn btn-ghost" href="<?= e(url('/brands')) ?>">نمایش همه برندها</a>
      </div>
    <?php else: ?>
      <ul class="brand-grid">
        <?php foreach ($pager['data'] as $index => $brand): ?>
        <li data-reveal>
          <a class="brand-card" href="<?= e(url('/brands/' . $brand['slug'])) ?>">
            <span class="brand-card-media">
              <?php if (!empty($brand['cover'])): ?>
                <img src="<?= e(upload_url($brand['cover'])) ?>" alt="<?= e($brand['name']) ?>" loading="lazy">
              <?php elseif (!empty($brand['logo'])): ?>
                <img src="<?= e(upload_url($brand['logo'])) ?>" alt="<?= e($brand['name']) ?>" loading="lazy">
              <?php else: ?>
                <span class="brand-fallback" aria-hidden="true"><?= e(mb_substr($brand['name'], 0, 1)) ?></span>
              <?php endif; ?>
            </span>
            <span class="brand-card-body">
              <?php if (!empty($brand['category_title'])): ?>
              <em class="work-tag"><?= e($brand['category_title']) ?></em>
              <?php endif; ?>
              <strong><?= e($brand['name']) ?></strong>
              <?php if (!empty($brand['name_en'])): ?>
              <small class="brand-en" dir="ltr"><?= e($brand['name_en']) ?></small>
              <?php endif; ?>
              <?php if (!empty($brand['industry'])): ?>
              <small><?= e($brand['industry']) ?></small>
              <?php endif; ?>
              <span class="brand-card-foot">
                <span><?= e(jdigits($brand['works_count'] ?? 0)) ?> پروژه</span>
                <span aria-hidden="true">↗</span>
              </span>
            </span>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>

      <?= pagination_links($pager, '/brands') ?>
    <?php endif; ?>

  </div>
</section>

<?php View::stop(); ?>
