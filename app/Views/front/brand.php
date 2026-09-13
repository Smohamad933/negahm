<?php
use App\Core\View;
View::extend('layouts.front');
$socials = $socials ?? [];
?>
<?php View::start('content'); ?>

<?= partial('partials.page-hero', [
    'title'  => $brand['name'],
    'lead'   => $brand['excerpt'],
    'crumbs' => [
        ['label' => 'خانه', 'url' => url('/')],
        ['label' => 'برندها', 'url' => url('/brands')],
        ['label' => $brand['name'], 'url' => ''],
    ],
]) ?>

<article class="brand-detail">
  <div class="container">

    <header class="brand-head">
      <div class="brand-head-id">
        <?php if (!empty($brand['logo'])): ?>
          <img class="brand-head-logo" src="<?= e(upload_url($brand['logo'])) ?>" alt="<?= e($brand['name']) ?>">
        <?php endif; ?>
        <div>
          <?php if (!empty($brand['category_title'])): ?>
          <p class="eyebrow"><?= e($brand['category_title']) ?></p>
          <?php endif; ?>
          <h2><?= e($brand['name']) ?></h2>
          <?php if (!empty($brand['name_en'])): ?>
          <p class="brand-en" dir="ltr"><?= e($brand['name_en']) ?></p>
          <?php endif; ?>
        </div>
      </div>

      <?php if (!empty($brand['cover'])): ?>
      <figure class="brand-head-cover">
        <img src="<?= e(upload_url($brand['cover'])) ?>" alt="<?= e($brand['name']) ?>">
      </figure>
      <?php endif; ?>
    </header>

    <div class="brand-layout">

      <div class="brand-main">
        <?php if (!empty($brand['body'])): ?>
        <section class="rich-text" data-reveal>
          <?= $brand['body'] ?>
        </section>
        <?php endif; ?>

        <?php if (!empty($brand['video_url'])): ?>
        <section class="brand-video" data-reveal>
          <h3>ویدئوی معرفی</h3>
          <div class="video-frame">
            <iframe src="<?= e($brand['video_url']) ?>" title="ویدئوی <?= e($brand['name']) ?>"
                    loading="lazy" allowfullscreen allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
          </div>
        </section>
        <?php endif; ?>

        <?php if (!empty($works['data'])): ?>
        <section class="brand-works" data-reveal>
          <div class="section-head section-head-row">
            <div>
              <p class="eyebrow">پروژه‌های اجرا شده</p>
              <h3 class="section-title-sm">کارهایی که برای <?= e($brand['name']) ?> انجام دادیم.</h3>
            </div>
            <span class="badge"><?= e(jdigits($works['total'])) ?> پروژه</span>
          </div>

          <div class="work-grid">
            <?php foreach ($works['data'] as $work): ?>
            <a class="work-card" href="<?= e(url('/works/' . $work['slug'])) ?>">
              <span class="work-media">
                <?php if (!empty($work['cover'])): ?>
                  <img src="<?= e(upload_url($work['cover'])) ?>" alt="<?= e($work['title']) ?>" loading="lazy">
                <?php else: ?>
                  <span class="work-media-fallback" aria-hidden="true"><?= e(mb_substr($work['title'], 0, 1)) ?></span>
                <?php endif; ?>
              </span>
              <span class="work-body">
                <?php if (!empty($work['category_title'])): ?>
                <em class="work-tag"><?= e($work['category_title']) ?></em>
                <?php endif; ?>
                <strong><?= e($work['title']) ?></strong>
                <?php if (!empty($work['year'])): ?><small><?= e(jdigits($work['year'])) ?></small><?php endif; ?>
              </span>
              <span class="work-arrow" aria-hidden="true">↗</span>
            </a>
            <?php endforeach; ?>
          </div>

          <?= pagination_links($works, '/brands/' . $brand['slug']) ?>
        </section>
        <?php endif; ?>
      </div>

      <aside class="brand-side">
        <div class="side-card">
          <h3>اطلاعات همکاری</h3>
          <dl class="info-list">
            <?php if (!empty($brand['industry'])): ?>
            <div><dt>صنعت</dt><dd><?= e($brand['industry']) ?></dd></div>
            <?php endif; ?>
            <?php if (!empty($brand['location'])): ?>
            <div><dt>موقعیت</dt><dd><?= e($brand['location']) ?></dd></div>
            <?php endif; ?>
            <?php if (!empty($brand['started_at'])): ?>
            <div><dt>شروع همکاری</dt><dd><?= e(jdigits($brand['started_at'])) ?></dd></div>
            <?php endif; ?>
            <?php if (!empty($brand['website'])): ?>
            <div><dt>وب‌سایت</dt><dd><a href="<?= e($brand['website']) ?>" target="_blank" rel="noopener nofollow"><?= e(preg_replace('#^https?://#', '', $brand['website'])) ?> ↗</a></dd></div>
            <?php endif; ?>
            <div><dt>پروژه‌ها</dt><dd><?= e(jdigits($brand['works_count'] ?? 0)) ?> مورد</dd></div>
          </dl>

          <?php if (!empty($brand['tags'])): ?>
          <ul class="tag-row">
            <?php foreach (array_filter(array_map('trim', preg_split('/[,،]+/u', $brand['tags']) ?: [])) as $tag): ?>
            <li><span><?= e($tag) ?></span></li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>

          <?php if ($socials !== []): ?>
          <ul class="social-list social-list-side" aria-label="شبکه‌های اجتماعی برند">
            <?php foreach ($socials as $key => $link): ?>
            <li><a href="<?= e($link) ?>" target="_blank" rel="noopener nofollow">
              <span aria-hidden="true"><?= e(icon_for_social($key)) ?></span> <?= e(social_label($key)) ?></a></li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>

          <a class="btn btn-primary btn-block" href="<?= e(url('/contact')) ?>">پروژه‌ای مشابه دارید؟</a>
        </div>
      </aside>

    </div>

    <?php if (!empty($related)): ?>
    <section class="related-brands">
      <div class="section-head">
        <p class="eyebrow">برندهای هم‌مسیر</p>
        <h3 class="section-title-sm">شاید این همکاری‌ها را هم بپسندید.</h3>
      </div>
      <ul class="brand-grid brand-grid-sm">
        <?php foreach ($related as $item): ?>
        <li>
          <a class="brand-card" href="<?= e(url('/brands/' . $item['slug'])) ?>">
            <span class="brand-card-media">
              <?php if (!empty($item['logo'])): ?>
                <img src="<?= e(upload_url($item['logo'])) ?>" alt="<?= e($item['name']) ?>" loading="lazy">
              <?php else: ?>
                <span class="brand-fallback" aria-hidden="true"><?= e(mb_substr($item['name'], 0, 1)) ?></span>
              <?php endif; ?>
            </span>
            <span class="brand-card-body"><strong><?= e($item['name']) ?></strong></span>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
    </section>
    <?php endif; ?>

  </div>
</article>

<?= json_ld([
    '@type' => 'Organization',
    'name'  => $brand['name'],
    'url'   => site_url('/brands/' . $brand['slug']),
    'description' => excerpt((string) ($brand['excerpt'] ?: $brand['body']), 200),
]) ?>

<?php View::stop(); ?>
