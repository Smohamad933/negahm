<?php
/**
 * صفحه اصلی — ساختار و ترتیب بخش‌ها مطابق negahm.ir
 */
use App\Core\View;
View::extend('layouts.front');
/** @var array<int,array<string,mixed>> $services $stats $process $brands $marquee $works $testimonials $posts */
$marquee      = $marquee ?? [];
$services     = $services ?? [];
$stats        = $stats ?? [];
$process      = $process ?? [];
$works        = $works ?? [];
$testimonials = $testimonials ?? [];
$quote        = $quote ?? '';
?>
<?php View::start('content'); ?>

<!-- ============================================================ HERO -->
<section class="hero">
  <div class="hero-media"></div>
  <div class="hero-noise"></div>
  <div class="hero-orb orb-one"></div>
  <div class="hero-orb orb-two"></div>

  <div class="container hero-inner">
    <div class="hero-copy">
      <p class="eyebrow"><i aria-hidden="true"></i><?= e($heroKicker ?? '') ?></p>

      <h1 class="hero-title"><?= emphasize((string) ($heroTitle ?? '')) ?></h1>

      <p><?= e($heroLead ?? '') ?></p>

      <div class="hero-actions">
        <a class="btn btn-primary" href="<?= e(url('/contact')) ?>">شروع یک پروژه <span aria-hidden="true">←</span></a>
        <a class="btn btn-ghost" href="<?= e(url('/works')) ?>">دیدن نمونه‌کارها <span aria-hidden="true">↗</span></a>
      </div>

      <?php if ($stats !== []): ?>
      <div class="hero-meta">
        <?php foreach ($stats as $stat): ?>
        <div>
          <span class="hero-meta-num" data-count="<?= e((string) $stat['value']) ?>"><?= e(jdigits((string) $stat['value']) . (string) ($stat['suffix'] ?? '')) ?></span>
          <span class="hero-meta-label"><?= e((string) $stat['label']) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <p class="hero-note"><span class="note-line" aria-hidden="true"></span><?= e($siteTagline ?? 'آژانس خلاق و تبلیغاتی') ?></p>
    </div>

    <div class="hero-side">
      <div class="hero-card">
        <span class="hero-card-index"><?= e(jdigits('01')) ?> / <?= e(jdigits((string) max(1, count($process)))) ?></span>
        <strong>Creative<br>Direction</strong>
        <span class="hero-card-bottom">استراتژی × طراحی × اجرا</span>
      </div>
    </div>
  </div>

  <p class="hero-scroll"><span aria-hidden="true">↓</span> اسکرول کنید</p>
</section>

<!-- ========================================================= CLIENTS -->
<?php if ($marquee !== []): ?>
<section class="clients">
  <div class="container">
    <p class="section-kicker">همراهان نگاه</p>

    <div class="clients-head">
      <h2><?= emphasize((string) setting('clients_title', 'برندهایی که *به ما اعتماد کردند*.')) ?></h2>
      <p><?= e((string) setting('clients_lead', 'همکاری برای ما فقط تحویل یک پروژه نیست؛ ساختن یک رابطه‌ی حرفه‌ای و ماندگار است.')) ?></p>
    </div>
  </div>

  <?php
    // اگر تعداد برندها کم باشد، یک دورِ تراک از عرض صفحه باریک‌تر می‌شود و
    // وسط لوپ جای خالی می‌افتد؛ پس گروه را تا رسیدن به آستانه تکرار می‌کنیم.
    $marqueeGroup = $marquee;
    while (count($marqueeGroup) < 16) {
        $marqueeGroup = array_merge($marqueeGroup, $marquee);
    }
  ?>
  <div class="marquee-wrapper">
    <div class="marquee-track">
      <div class="marquee-group">
        <?php foreach ($marqueeGroup as $brand): ?>
        <span class="client-pill"><?= e((string) $brand['name']) ?></span>
        <?php endforeach; ?>
      </div>
      <!-- کپی دوم فقط برای بی‌درز شدن لوپ است؛ از صفحه‌خوان پنهانش می‌کنیم -->
      <div class="marquee-group" aria-hidden="true">
        <?php foreach ($marqueeGroup as $brand): ?>
        <span class="client-pill"><?= e((string) $brand['name']) ?></span>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ======================================================== SERVICES -->
<?php if ($services !== []): ?>
<section class="section services">
  <div class="container">

    <div class="section-intro">
      <div>
        <p class="section-kicker"><?= e((string) setting('services_kicker', 'چه کاری انجام می‌دهیم؟')) ?></p>
        <h2><?= emphasize((string) setting('services_title', 'همه‌چیز برای *رشد یک برند*.')) ?></h2>
      </div>
      <p><?= e((string) setting('services_lead', 'خدمات نگاه مدیا از استراتژی شروع می‌شود و تا اجرای دقیق ادامه پیدا می‌کند؛ یک تیم، یک مسیر و یک خروجی منسجم.')) ?></p>
    </div>

    <div class="services-grid">
      <?php foreach ($services as $index => $service): ?>
      <article class="service-card<?= $index === 0 ? ' featured' : '' ?>">
        <span class="service-number"><?= e(jdigits(sprintf('%02d', $index + 1))) ?></span>
        <span class="service-icon" aria-hidden="true"><?= e((string) ($service['icon'] ?: '✦')) ?></span>
        <h3><?= e((string) $service['title']) ?></h3>
        <p><?= e(excerpt((string) $service['excerpt'], 120)) ?></p>
        <a href="<?= e(url('/services/' . $service['slug'])) ?>">بیشتر بدانید <span aria-hidden="true">←</span></a>
      </article>
      <?php endforeach; ?>
    </div>

  </div>
</section>
<?php endif; ?>

<!-- =========================================================== ABOUT -->
<section class="section-dark about">
  <div class="container about-grid">

    <div class="about-frame">
      <span class="about-big" aria-hidden="true">N</span>
      <span class="about-caption">NEGĀH / MEDIA</span>
    </div>

    <div class="about-copy">
      <p class="section-kicker light"><?= e((string) setting('about_kicker', 'نگاه ما')) ?></p>
      <h2><?= emphasize((string) setting('about_title', 'ما فقط تبلیغ *نمی‌کنیم؛ روایت می‌سازیم*.')) ?></h2>
      <p><?= e((string) ($aboutText !== '' ? $aboutText : setting('about_short', 'نگاه مدیا یک تیم خلاق برای برندهایی است که می‌خواهند متفاوت فکر کنند. ما بین استراتژی، طراحی و محتوا پل می‌زنیم تا هر خروجی بخشی از یک تصویر بزرگ‌تر باشد.'))) ?></p>

      <?php if ($stats !== []): ?>
      <div class="about-stats">
        <?php foreach ($stats as $stat): ?>
        <div>
          <strong><?= e(jdigits((string) $stat['value']) . (string) ($stat['suffix'] ?? '')) ?></strong>
          <span><?= e((string) $stat['label']) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <div class="hero-actions">
        <a class="btn btn-primary" href="<?= e(url('/about')) ?>">بیشتر درباره ما <span aria-hidden="true">←</span></a>
      </div>
    </div>

  </div>
</section>

<!-- ============================================================ WORK -->
<?php if ($works !== []): ?>
<section class="section work">
  <div class="container">

    <div class="section-intro">
      <div>
        <p class="section-kicker"><?= e((string) setting('work_kicker', 'نمونه‌کارها')) ?></p>
        <h2><?= emphasize((string) setting('work_title', 'کارهایی که *حرف می‌زنند*.')) ?></h2>
      </div>
      <a class="text-link" href="<?= e(url('/works')) ?>">مشاهده همه پروژه‌ها <span aria-hidden="true">←</span></a>
    </div>

    <div class="work-grid">
      <?php foreach (array_slice($works, 0, 4) as $index => $work): ?>
      <a class="work-item<?= $index === 0 ? ' work-large' : '' ?>" href="<?= e(url('/works/' . $work['slug'])) ?>">
        <?php if (!empty($work['cover'])): ?>
        <img src="<?= e(upload_url((string) $work['cover'])) ?>" alt="<?= e((string) $work['title']) ?>" loading="lazy">
        <?php else: ?>
        <span class="work-media-fallback" aria-hidden="true">◇</span>
        <?php endif; ?>
        <span class="work-overlay">
          <span><?= e((string) ($work['category_title'] ?? 'پروژه')) ?></span>
          <h3><?= e((string) $work['title']) ?></h3>
          <b class="work-arrow" aria-hidden="true">↗</b>
        </span>
      </a>
      <?php endforeach; ?>
    </div>

  </div>
</section>
<?php endif; ?>

<!-- ========================================================= PROCESS -->
<?php if ($process !== []): ?>
<section class="section-soft">
  <div class="container">

    <div class="section-intro">
      <div>
        <p class="section-kicker"><?= e((string) setting('process_kicker', 'چطور همکاری می‌کنیم؟')) ?></p>
        <h2><?= emphasize((string) setting('process_title', 'ساده، شفاف، *حساب‌شده*.')) ?></h2>
      </div>
      <p><?= e((string) setting('process_lead', 'هر پروژه مسیر خودش را دارد، اما چارچوب ما همیشه روشن است.')) ?></p>
    </div>

    <div class="process-grid">
      <?php foreach ($process as $index => $step): ?>
      <div class="process-step">
        <span><?= e((string) ($step['number'] ?: jdigits(sprintf('%02d', $index + 1)))) ?></span>
        <h3><?= e((string) $step['title']) ?></h3>
        <p><?= e((string) $step['body']) ?></p>
      </div>
      <?php endforeach; ?>
    </div>

  </div>
</section>
<?php endif; ?>

<!-- ===================================================== TESTIMONIAL -->
<?php if ($quote !== '' || $testimonials !== []): ?>
<section class="section-dark testimonial">
  <div class="container testimonial-inner">
    <?php
    $active = $testimonials !== [] ? $testimonials[0] : null;
    $body   = (string) ($active['body'] ?? $quote);
    $author = (string) ($active['author'] ?? 'نگاه مدیا — Creative Agency');
    ?>
    <span class="quote-mark" aria-hidden="true">&ldquo;</span>
    <blockquote><?= e($body) ?></blockquote>
    <span class="quote-line" aria-hidden="true"></span>
    <span><?= e($author) ?></span>
  </div>
</section>
<?php endif; ?>

<!-- ========================================================= CONTACT -->
<section class="contact" id="contact">
  <div class="container contact-inner">
    <p class="section-kicker">پروژه بعدی شما</p>
    <h2><?= emphasize((string) setting('cta_title', 'بیایید چیزی *قابلِ دیدن* بسازیم.')) ?></h2>
    <p><?= e((string) setting('cta_lead', 'اگر ایده‌ای دارید یا نمی‌دانید از کجا شروع کنید، با ما صحبت کنید.')) ?></p>

    <div class="contact-actions">
      <?php if (!empty($sitePhone)): ?>
      <a class="btn btn-dark" href="tel:<?= e($sitePhone) ?>">درخواست مشاوره <span aria-hidden="true">←</span></a>
      <?php endif; ?>
      <?php if (!empty($siteEmail)): ?>
      <a class="btn btn-ghost" style="border-color:var(--line);color:var(--ink)" href="mailto:<?= e($siteEmail) ?>"><?= e($siteEmail) ?> <span aria-hidden="true">↗</span></a>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php View::stop(); ?>
