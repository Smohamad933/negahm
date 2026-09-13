<?php
use App\Core\View;
View::extend('layouts.front');
$neighbours = $neighbours ?? ['prev' => null, 'next' => null];
$services   = $services ?? [];
$gallery    = $gallery ?? [];
?>
<?php View::start('content'); ?>

<article class="work-detail">

  <header class="work-hero">
    <div class="container">
      <nav class="breadcrumb" aria-label="مسیر">
        <ol>
          <li><a href="<?= e(url('/')) ?>">خانه</a></li>
          <li><a href="<?= e(url('/works')) ?>">نمونه‌کارها</a></li>
          <?php if (!empty($work['category_slug'])): ?>
          <li><a href="<?= e(url('/works/category/' . $work['category_slug'])) ?>"><?= e($work['category_title']) ?></a></li>
          <?php endif; ?>
          <li><span aria-current="page"><?= e($work['title']) ?></span></li>
        </ol>
      </nav>

      <?php if (!empty($work['label_en']) || !empty($work['category_label'])): ?>
      <p class="eyebrow eyebrow-en" dir="ltr"><?= e($work['label_en'] ?: $work['category_label']) ?></p>
      <?php endif; ?>

      <h1 class="page-title"><?= e($work['title']) ?></h1>

      <?php if (!empty($work['excerpt'])): ?>
      <p class="page-lead"><?= e($work['excerpt']) ?></p>
      <?php endif; ?>

      <ul class="work-meta">
        <?php if (!empty($work['brand_slug'])): ?>
        <li><span>برند</span><a href="<?= e(url('/brands/' . $work['brand_slug'])) ?>"><?= e($work['brand_name']) ?></a></li>
        <?php elseif (!empty($work['client_name'])): ?>
        <li><span>کارفرما</span><em><?= e($work['client_name']) ?></em></li>
        <?php endif; ?>
        <?php if (!empty($work['year'])): ?>
        <li><span>سال</span><em><?= e(jdigits($work['year'])) ?></em></li>
        <?php endif; ?>
        <?php if (!empty($work['duration'])): ?>
        <li><span>مدت</span><em><?= e($work['duration']) ?></em></li>
        <?php endif; ?>
        <?php if (!empty($work['category_title'])): ?>
        <li><span>دسته</span><a href="<?= e(url('/works/category/' . $work['category_slug'])) ?>"><?= e($work['category_title']) ?></a></li>
        <?php endif; ?>
      </ul>
    </div>

    <?php if (!empty($work['cover'])): ?>
    <figure class="work-hero-media">
      <img src="<?= e(upload_url($work['cover'])) ?>" alt="<?= e($work['title']) ?>">
    </figure>
    <?php endif; ?>
  </header>

  <div class="container">

    <?php if ($services !== []): ?>
    <ul class="tag-row tag-row-lg">
      <?php foreach ($services as $service): ?>
      <li><span><?= e($service) ?></span></li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>

    <?php if (!empty($work['video_url'])): ?>
    <section class="work-video" data-reveal>
      <div class="video-frame">
        <iframe src="<?= e($work['video_url']) ?>" title="<?= e($work['title']) ?>" loading="lazy" allowfullscreen
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
      </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($work['challenge']) || !empty($work['solution']) || !empty($work['result'])): ?>
    <section class="case-grid">
      <?php foreach ([['چالش', $work['challenge']], ['راه‌حل', $work['solution']], ['نتیجه', $work['result']]] as [$label, $text]): ?>
        <?php if (!empty($text)): ?>
        <div class="case-block" data-reveal>
          <h2><?= e($label) ?></h2>
          <div class="rich-text"><?= \App\Core\Str::nl2p((string) $text) ?></div>
        </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </section>
    <?php endif; ?>

    <?php if (!empty($work['body'])): ?>
    <section class="rich-text rich-text-lg" data-reveal>
      <?= $work['body'] ?>
    </section>
    <?php endif; ?>

    <?php if ($gallery !== []): ?>
    <section class="work-gallery">
      <div class="section-head">
        <p class="eyebrow">گالری پروژه</p>
        <h2 class="section-title-sm">خروجی‌های بصری</h2>
      </div>
      <div class="gallery-grid" data-lightbox>
        <?php foreach ($gallery as $item): ?>
        <a class="gallery-item" href="<?= e(upload_url($item['path'])) ?>" data-caption="<?= e($item['caption'] ?: $item['alt'] ?: $work['title']) ?>">
          <img src="<?= e(upload_url($item['path'])) ?>" alt="<?= e($item['alt'] ?: $work['title']) ?>" loading="lazy">
          <?php if (!empty($item['caption'])): ?>
          <span class="gallery-caption"><?= e($item['caption']) ?></span>
          <?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <nav class="work-nav" aria-label="پروژه قبلی و بعدی">
      <?php if (!empty($neighbours['prev'])): ?>
      <a class="work-nav-link" href="<?= e(url('/works/' . $neighbours['prev']['slug'])) ?>" rel="prev">
        <span>پروژه قبلی</span><strong><?= e($neighbours['prev']['title']) ?></strong>
      </a>
      <?php else: ?><span></span><?php endif; ?>

      <?php if (!empty($neighbours['next'])): ?>
      <a class="work-nav-link work-nav-next" href="<?= e(url('/works/' . $neighbours['next']['slug'])) ?>" rel="next">
        <span>پروژه بعدی</span><strong><?= e($neighbours['next']['title']) ?></strong>
      </a>
      <?php endif; ?>
    </nav>

    <?php if (!empty($related)): ?>
    <section class="related-works">
      <div class="section-head section-head-row">
        <div>
          <p class="eyebrow">پروژه‌های مرتبط</p>
          <h2 class="section-title-sm">کارهای مشابه</h2>
        </div>
        <a class="link-arrow" href="<?= e(url('/works')) ?>">همه پروژه‌ها <span aria-hidden="true">←</span></a>
      </div>

      <div class="work-grid">
        <?php foreach ($related as $item): ?>
        <a class="work-card" href="<?= e(url('/works/' . $item['slug'])) ?>">
          <span class="work-media">
            <?php if (!empty($item['cover'])): ?>
              <img src="<?= e(upload_url($item['cover'])) ?>" alt="<?= e($item['title']) ?>" loading="lazy">
            <?php else: ?>
              <span class="work-media-fallback" aria-hidden="true"><?= e(mb_substr($item['title'], 0, 1)) ?></span>
            <?php endif; ?>
          </span>
          <span class="work-body">
            <?php if (!empty($item['category_title'])): ?><em class="work-tag"><?= e($item['category_title']) ?></em><?php endif; ?>
            <strong><?= e($item['title']) ?></strong>
          </span>
          <span class="work-arrow" aria-hidden="true">↗</span>
        </a>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <section class="cta-band">
      <div>
        <h2>پروژه‌ای مشابه در ذهن دارید؟</h2>
        <p>بیایید درباره‌اش صحبت کنیم؛ از ایده تا اجرا همراه شما هستیم.</p>
      </div>
      <a class="btn btn-primary btn-lg" href="<?= e(url('/contact')) ?>">درخواست مشاوره رایگان <span aria-hidden="true">←</span></a>
    </section>

  </div>
</article>

<div class="lightbox" data-lightbox-overlay hidden>
  <button class="lightbox-close" type="button" data-lightbox-close aria-label="بستن">×</button>
  <img data-lightbox-img src="" alt="">
  <p data-lightbox-caption></p>
</div>

<?= json_ld([
    '@type' => 'CreativeWork',
    'name'  => $work['title'],
    'url'   => site_url('/works/' . $work['slug']),
    'description' => excerpt((string) ($work['excerpt'] ?: $work['body']), 200),
    'dateCreated' => $work['published_at'] ?? $work['created_at'] ?? null,
    'creator' => ['@type' => 'Organization', 'name' => $siteName ?? 'نگاه مدیا'],
]) ?>

<?php View::stop(); ?>
