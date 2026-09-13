<?php
use App\Core\View;
View::extend('layouts.front');
$items  = $items ?? [];
$others = $others ?? [];
$faqs   = $faqs ?? [];
?>
<?php View::start('content'); ?>

<article class="service-detail">
  <div class="container">

    <nav class="breadcrumb" aria-label="مسیر">
      <ol>
        <li><a href="<?= e(url('/')) ?>">خانه</a></li>
        <li><a href="<?= e(url('/services')) ?>">خدمات</a></li>
        <li><span aria-current="page"><?= e($service['title']) ?></span></li>
      </ol>
    </nav>

    <header class="service-head">
      <div>
        <span class="service-icon service-icon-lg" aria-hidden="true"><?= e($service['icon'] ?: '✦') ?></span>
        <h1 class="page-title"><?= e($service['title']) ?></h1>
        <?php if (!empty($service['label_en'])): ?>
        <p class="service-en" dir="ltr"><?= e($service['label_en']) ?></p>
        <?php endif; ?>
        <?php if (!empty($service['excerpt'])): ?>
        <p class="page-lead"><?= e($service['excerpt']) ?></p>
        <?php endif; ?>
        <div class="hero-actions">
          <a class="btn btn-primary" href="<?= e(url('/contact?service=' . (int) $service['id'])) ?>">درخواست این خدمت <span aria-hidden="true">←</span></a>
          <a class="btn btn-ghost" href="<?= e(url('/works')) ?>">دیدن نمونه‌کارها</a>
        </div>
      </div>

      <?php if (!empty($service['image'])): ?>
      <figure class="service-head-media">
        <img src="<?= e(upload_url($service['image'])) ?>" alt="<?= e($service['title']) ?>">
      </figure>
      <?php endif; ?>
    </header>

    <div class="service-layout">
      <div class="service-main">
        <?php if (!empty($service['body'])): ?>
        <section class="rich-text" data-reveal><?= $service['body'] ?></section>
        <?php endif; ?>

        <?php if ($items !== []): ?>
        <section class="service-deliverables" data-reveal>
          <h2>چه چیزهایی تحویل می‌گیرید؟</h2>
          <ul class="check-list">
            <?php foreach ($items as $item): ?>
            <li><?= e($item) ?></li>
            <?php endforeach; ?>
          </ul>
        </section>
        <?php endif; ?>

        <?php if (!empty($works['data'])): ?>
        <section class="service-works">
          <div class="section-head section-head-row">
            <div>
              <p class="eyebrow">نمونه‌کارها</p>
              <h2 class="section-title-sm">پروژه‌های مرتبط با این خدمت</h2>
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
                <strong><?= e($work['title']) ?></strong>
                <?php if (!empty($work['brand_name'])): ?><small><?= e($work['brand_name']) ?></small><?php endif; ?>
              </span>
              <span class="work-arrow" aria-hidden="true">↗</span>
            </a>
            <?php endforeach; ?>
          </div>
        </section>
        <?php endif; ?>
      </div>

      <aside class="service-side">
        <div class="side-card">
          <h3>همین خدمت را می‌خواهید؟</h3>
          <p>یک فرم کوتاه پر کنید؛ ما در اولین فرصت با شما تماس می‌گیریم.</p>
          <?php if (!empty($service['price_note'])): ?>
          <p class="price-note"><?= e($service['price_note']) ?></p>
          <?php endif; ?>
          <a class="btn btn-primary btn-block" href="<?= e(url('/contact?service=' . (int) $service['id'])) ?>">شروع گفت‌وگو</a>
          <?php if (!empty($sitePhone)): ?>
          <a class="btn btn-ghost btn-block" href="tel:<?= e($sitePhone) ?>"><?= e(phone_display($sitePhone)) ?></a>
          <?php endif; ?>
        </div>

        <?php if ($others !== []): ?>
        <div class="side-card">
          <h3>سایر خدمات</h3>
          <ul class="side-links">
            <?php foreach ($others as $other): ?>
            <li><a href="<?= e(url('/services/' . $other['slug'])) ?>">
              <span aria-hidden="true"><?= e($other['icon'] ?: '✦') ?></span> <?= e($other['title']) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <?php endif; ?>
      </aside>
    </div>

    <?php if (!empty($process)): ?>
    <section class="process process-inline">
      <div class="section-head">
        <p class="eyebrow">فرآیند همکاری</p>
        <h2 class="section-title-sm">مسیر اجرای پروژه</h2>
      </div>
      <ol class="process-list">
        <?php foreach ($process as $index => $step): ?>
        <li class="process-step">
          <span class="process-num"><?= e($step['number'] ?: jdigits(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT))) ?></span>
          <h3><?= e($step['title']) ?></h3>
          <p><?= e($step['body']) ?></p>
        </li>
        <?php endforeach; ?>
      </ol>
    </section>
    <?php endif; ?>

    <?php if ($faqs !== []): ?>
    <section class="faq-section">
      <div class="section-head">
        <p class="eyebrow">سوالات متداول</p>
        <h2 class="section-title-sm">پرسش‌های رایج درباره این خدمت</h2>
      </div>
      <div class="accordion">
        <?php foreach ($faqs as $index => $faq): ?>
        <details class="accordion-item" <?= $index === 0 ? 'open' : '' ?>>
          <summary><?= e($faq['question']) ?></summary>
          <div class="accordion-body"><?= \App\Core\Str::nl2p((string) $faq['answer']) ?></div>
        </details>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($brands)): ?>
    <section class="service-brands">
      <div class="section-head">
        <p class="eyebrow">همراهان نگاه</p>
        <h2 class="section-title-sm">برندهایی که این خدمت را از ما گرفته‌اند</h2>
      </div>
      <ul class="brand-grid brand-grid-sm">
        <?php foreach ($brands as $brand): ?>
        <li>
          <a class="brand-card" href="<?= e(url('/brands/' . $brand['slug'])) ?>">
            <span class="brand-card-media">
              <?php if (!empty($brand['logo'])): ?>
                <img src="<?= e(upload_url($brand['logo'])) ?>" alt="<?= e($brand['name']) ?>" loading="lazy">
              <?php else: ?>
                <span class="brand-fallback" aria-hidden="true"><?= e(mb_substr($brand['name'], 0, 1)) ?></span>
              <?php endif; ?>
            </span>
            <span class="brand-card-body"><strong><?= e($brand['name']) ?></strong></span>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
    </section>
    <?php endif; ?>

  </div>
</article>

<?= json_ld([
    '@type' => 'Service',
    'name'  => $service['title'],
    'description' => excerpt((string) ($service['excerpt'] ?: $service['body']), 200),
    'provider' => ['@type' => 'Organization', 'name' => $siteName ?? 'نگاه مدیا', 'url' => site_url('/')],
    'url'   => site_url('/services/' . $service['slug']),
]) ?>

<?php View::stop(); ?>
