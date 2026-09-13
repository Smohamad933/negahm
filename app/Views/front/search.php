<?php
use App\Core\View;
View::extend('layouts.front'); ?>
<?php View::start('content'); ?>

<?= partial('partials.page-hero', [
    'title'  => $q !== '' ? 'نتایج جست‌وجو برای «' . $q . '»' : 'جست‌وجو در سایت',
    'lead'   => $q !== '' ? jdigits($total) . ' مورد یافت شد.' : 'بین نمونه‌کارها، برندها، خدمات و نوشته‌ها بگردید.',
    'crumbs' => [
        ['label' => 'خانه', 'url' => url('/')],
        ['label' => 'جست‌وجو', 'url' => ''],
    ],
]) ?>

<section class="listing">
  <div class="container">

    <form class="filter-search filter-search-lg" action="<?= e(url('/search')) ?>" method="get" role="search">
      <label class="sr-only" for="search-q">عبارت جست‌وجو</label>
      <input id="search-q" type="search" name="q" value="<?= e($q) ?>" placeholder="چه چیزی را دنبال می‌کنید؟" autofocus>
      <button type="submit">جست‌وجو</button>
    </form>

    <?php if ($q !== ''): ?>

      <?php if ($total === 0): ?>
        <div class="empty-state">
          <p>نتیجه‌ای برای «<?= e($q) ?>» پیدا نشد.</p>
          <a class="btn btn-ghost" href="<?= e(url('/works')) ?>">دیدن نمونه‌کارها</a>
        </div>
      <?php else: ?>

        <?php if (!empty($results['works'])): ?>
        <section class="search-group">
          <h2 class="section-title-sm">نمونه‌کارها <small><?= e(jdigits(count($results['works']))) ?></small></h2>
          <div class="work-grid">
            <?php foreach ($results['works'] as $work): ?>
            <a class="work-card" href="<?= e(url('/works/' . $work['slug'])) ?>">
              <span class="work-media">
                <?php if (!empty($work['cover'])): ?><img src="<?= e(upload_url($work['cover'])) ?>" alt="" loading="lazy"><?php endif; ?>
              </span>
              <span class="work-body">
                <?php if (!empty($work['category_title'])): ?><em class="work-tag"><?= e($work['category_title']) ?></em><?php endif; ?>
                <strong><?= e($work['title']) ?></strong>
                <small><?= e(excerpt((string) $work['excerpt'], 80)) ?></small>
              </span>
            </a>
            <?php endforeach; ?>
          </div>
        </section>
        <?php endif; ?>

        <?php if (!empty($results['brands'])): ?>
        <section class="search-group">
          <h2 class="section-title-sm">برندها <small><?= e(jdigits(count($results['brands']))) ?></small></h2>
          <ul class="brand-grid brand-grid-sm">
            <?php foreach ($results['brands'] as $brand): ?>
            <li>
              <a class="brand-card" href="<?= e(url('/brands/' . $brand['slug'])) ?>">
                <span class="brand-card-media">
                  <?php if (!empty($brand['logo'])): ?><img src="<?= e(upload_url($brand['logo'])) ?>" alt="" loading="lazy"><?php endif; ?>
                </span>
                <span class="brand-card-body"><strong><?= e($brand['name']) ?></strong></span>
              </a>
            </li>
            <?php endforeach; ?>
          </ul>
        </section>
        <?php endif; ?>

        <?php if (!empty($results['services'])): ?>
        <section class="search-group">
          <h2 class="section-title-sm">خدمات <small><?= e(jdigits(count($results['services']))) ?></small></h2>
          <ul class="search-list">
            <?php foreach ($results['services'] as $service): ?>
            <li>
              <a href="<?= e(url('/services/' . $service['slug'])) ?>">
                <span aria-hidden="true"><?= e($service['icon'] ?: '✦') ?></span>
                <span>
                  <strong><?= e($service['title']) ?></strong>
                  <small><?= e(excerpt((string) $service['excerpt'], 90)) ?></small>
                </span>
              </a>
            </li>
            <?php endforeach; ?>
          </ul>
        </section>
        <?php endif; ?>

        <?php if (!empty($results['posts'])): ?>
        <section class="search-group">
          <h2 class="section-title-sm">نوشته‌ها <small><?= e(jdigits(count($results['posts']))) ?></small></h2>
          <div class="post-grid">
            <?php foreach ($results['posts'] as $post): ?>
            <a class="post-card" href="<?= e(url('/blog/' . $post['slug'])) ?>">
              <span class="post-media">
                <?php if (!empty($post['cover'])): ?><img src="<?= e(upload_url($post['cover'])) ?>" alt="" loading="lazy"><?php endif; ?>
              </span>
              <span class="post-body">
                <strong><?= e($post['title']) ?></strong>
                <small><?= e(excerpt((string) ($post['excerpt'] ?: $post['body']), 90)) ?></small>
              </span>
            </a>
            <?php endforeach; ?>
          </div>
        </section>
        <?php endif; ?>

      <?php endif; ?>

    <?php endif; ?>

  </div>
</section>

<?php View::stop(); ?>
