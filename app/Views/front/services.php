<?php
use App\Core\View;
View::extend('layouts.front'); ?>
<?php View::start('content'); ?>

<?= partial('partials.page-hero', [
    'title'  => 'همه‌چیز برای رشد یک برند.',
    'lead'   => 'خدمات نگاه مدیا از استراتژی شروع می‌شود و تا اجرای دقیق ادامه پیدا می‌کند؛ یک تیم، یک مسیر و یک خروجی منسجم.',
    'crumbs' => [
        ['label' => 'خانه', 'url' => url('/')],
        ['label' => 'خدمات', 'url' => ''],
    ],
]) ?>

<section class="listing">
  <div class="container">
    <?php if (empty($services)): ?>
      <div class="empty-state"><p>هنوز خدمتی ثبت نشده است.</p></div>
    <?php else: ?>
      <ol class="service-grid service-grid-lg">
        <?php foreach ($services as $index => $service): ?>
        <li class="service-card" data-reveal style="--i:<?= (int) $index ?>">
          <span class="service-num"><?= e($service['number'] ?: jdigits(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT))) ?></span>
          <span class="service-icon" aria-hidden="true"><?= e($service['icon'] ?: '✦') ?></span>
          <h3><a href="<?= e(url('/services/' . $service['slug'])) ?>"><?= e($service['title']) ?></a></h3>
          <?php if (!empty($service['label_en'])): ?>
          <p class="service-en" dir="ltr"><?= e($service['label_en']) ?></p>
          <?php endif; ?>
          <p class="service-excerpt"><?= e($service['excerpt']) ?></p>
          <?php $items = \App\Models\Service::decodeItems($service['items'] ?? null); ?>
          <?php if ($items !== []): ?>
          <ul class="service-items">
            <?php foreach (array_slice($items, 0, 5) as $item): ?>
            <li><?= e($item) ?></li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>
          <a class="link-arrow link-sm" href="<?= e(url('/services/' . $service['slug'])) ?>">جزئیات خدمت <span aria-hidden="true">←</span></a>
        </li>
        <?php endforeach; ?>
      </ol>
    <?php endif; ?>
  </div>
</section>

<?php if (!empty($process)): ?>
<section class="process">
  <div class="container">
    <div class="section-head">
      <p class="eyebrow">چطور همکاری می‌کنیم؟</p>
      <h2 class="section-title">ساده، شفاف، <em>حساب‌شده</em>.</h2>
    </div>
    <ol class="process-list">
      <?php foreach ($process as $index => $step): ?>
      <li class="process-step" data-reveal>
        <span class="process-num"><?= e($step['number'] ?: jdigits(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT))) ?></span>
        <h3><?= e($step['title']) ?></h3>
        <p><?= e($step['body']) ?></p>
      </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($testimonials)): ?>
<section class="testimonials">
  <div class="container">
    <div class="section-head">
      <p class="eyebrow">تجربه همکاری</p>
      <h2 class="section-title">مشتریان ما <em>چه می‌گویند</em>.</h2>
    </div>
    <div class="testimonial-grid">
      <?php foreach ($testimonials as $testimonial): ?>
      <figure class="testimonial-card" data-reveal>
        <blockquote><?= e($testimonial['quote']) ?></blockquote>
        <figcaption>
          <span class="avatar-fallback" aria-hidden="true"><?= e(mb_substr($testimonial['name'], 0, 1)) ?></span>
          <span>
            <strong><?= e($testimonial['name']) ?></strong>
            <small><?= e(trim(($testimonial['role'] ?? '') . (!empty($testimonial['company']) ? ' — ' . $testimonial['company'] : ''))) ?></small>
          </span>
        </figcaption>
      </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php View::stop(); ?>
